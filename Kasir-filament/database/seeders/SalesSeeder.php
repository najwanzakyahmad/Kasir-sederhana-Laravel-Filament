<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use App\Models\Sales;
use App\Models\SaleItems;
use App\Models\Products;
use App\Services\InventoryService;

class SalesSeeder extends Seeder
{
    // ==== KONFIGURASI ID KUSTOM (SAMAKAN DENGAN TRAIT-MU JIKA PERLU) ====
    private const ID_PREFIX = 'SALE-';
    private const ID_PAD    = 6; // hasil: SALE000001; ganti ke 5 kalau mau SALE00001

    public function run(): void
    {
        // if (Sales::exists()) return; // opsional

        $products = Products::where('is_active', true)->get(['id', 'sell_price', 'tax_rate']);
        if ($products->isEmpty()) {
            $this->command?->warn('SalesSeeder: Tidak ada produk aktif. Jalankan ProductSeeder dulu.');
            return;
        }

        $targetCount = 50;
        $remaining   = $targetCount;

        $start = Carbon::today()->subMonthsNoOverflow(3)->startOfDay(); // 3 bulan terakhir
        $end   = Carbon::today()->endOfDay();

        for ($day = $start->copy(); $day->lte($end) && $remaining > 0; $day->addDay()) {
            $salesToday = random_int(0, 5);
            $toCreate   = min($salesToday, $remaining);

            for ($s = 0; $s < $toCreate; $s++) {
                $ts = $day->copy()->setTime(random_int(8, 20), random_int(0, 59), random_int(0, 59));

                DB::transaction(function () use ($products, $ts) {
                    $status = Arr::random(['paid', 'draft']);
                    $isPaid = $status === 'paid';

                    // ====== GENERATE CUSTOM ID UNTUK SALES (dengan lock agar aman) ======
                    $saleId = $this->nextCustomSaleId();

                    // Buat sale
                    $sale = Sales::create([
                        'id'             => $saleId,      // <— isi id custom
                        'status'         => $status,
                        'subtotal'       => 0,
                        'discount_total' => 0,
                        'tax_total'      => 0,
                        'grand_total'    => 0,
                        'paid_total'     => 0,
                        'change_due'     => 0,
                        'paid_at'        => $isPaid ? $ts->toDateString() : null, // kolom kamu type DATE
                        'created_at'     => $ts,
                        'updated_at'     => $ts,
                    ]);

                    // 2–5 item acak
                    $itemsCount = random_int(2, 5);
                    $pickCount  = min($itemsCount, $products->count());
                    $picked     = $products->random($pickCount);

                    $subtotal = 0.0;
                    $discountTotal = 0.0;
                    $taxTotal = 0.0;

                    foreach ($picked as $p) {
                        $qty     = random_int(1, 5);
                        $price   = (float) $p->sell_price;
                        $taxRate = (float) ($p->tax_rate ?? 0);
                        $discount = Arr::random([0, 0, 1000, 2000]);

                        $lineBase  = max(($price * $qty) - $discount, 0);
                        $taxAmount = round($lineBase * ($taxRate / 100), 2);
                        $lineTotal = round($lineBase + $taxAmount, 2);

                        // Catatan: SaleItems kamu sudah punya custom id sendiri — biarkan model yg isi id
                        SaleItems::create([
                            'sale_id'    => $sale->id,
                            'product_id' => $p->id,
                            'qty'        => $qty,
                            'price'      => $price,
                            'tax_rate'   => $taxRate,
                            'discount'   => $discount,
                            'line_total' => $lineTotal,
                            'created_at' => $ts,
                            'updated_at' => $ts,
                        ]);

                        $subtotal      += ($price * $qty);
                        $discountTotal += $discount;
                        $taxTotal      += $taxAmount;
                    }

                    $subtotal      = round($subtotal, 2);
                    $discountTotal = round($discountTotal, 2);
                    $taxTotal      = round($taxTotal, 2);
                    $grandTotal    = round($subtotal - $discountTotal + $taxTotal, 2);
                    $paidTotal     = $isPaid ? $grandTotal : 0.0;

                    $sale->update([
                        'subtotal'       => $subtotal,
                        'discount_total' => $discountTotal,
                        'tax_total'      => $taxTotal,
                        'grand_total'    => $grandTotal,
                        'paid_total'     => $paidTotal,
                        'change_due'     => 0.0,
                        'updated_at'     => $ts,
                    ]);

                    // Kurangi stok kalau paid
                    if ($isPaid && ! $sale->stock_applied) {
                        try {
                            InventoryService::applyForSale($sale);
                        } catch (\Throwable $e) {
                            $this->command?->warn("Stock kurang untuk sale {$sale->id}: {$e->getMessage()} → set draft");
                            $sale->update([
                                'status'        => 'draft',
                                'paid_total'    => 0,
                                'paid_at'       => null,
                                'stock_applied' => false,
                            ]);
                        }
                    }
                });

                $remaining--;
                if ($remaining <= 0) {
                    $this->command?->info("SalesSeeder: $targetCount transaksi dibuat dalam 3 bulan terakhir.");
                    break;
                }
            }
        }
    }

    /**
     * Ambil ID terakhir dengan prefix, lalu naikkan 1 (SALE000001, SALE000002, ...)
     * Dipanggil di dalam transaksi agar lockForUpdate efektif.
     */
    private function nextCustomSaleId(): string
    {
        $prefix = self::ID_PREFIX;
        $pad    = self::ID_PAD;

        // Kunci baris terkait dengan lockForUpdate biar aman dari race
        $lastId = DB::table('sales')
            ->where('id', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->value('id');

        $nextNum = 1;
        if ($lastId && preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $lastId, $m)) {
            $nextNum = ((int) $m[1]) + 1;
        }

        return $prefix . str_pad((string) $nextNum, $pad, '0', STR_PAD_LEFT);
    }
}
