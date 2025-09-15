<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use App\Models\Sales;
use App\Models\SaleItems;
use App\Models\Products;

class SalesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kalau mau idempotent (hanya seed sekali): buka komentar baris berikut
        // if (Sales::exists()) return;

        $products = Products::where('is_active', true)
            ->get(['id', 'sell_price', 'tax_rate', 'name']);

        if ($products->isEmpty()) {
            $this->command?->warn('SalesSeeder: Tidak ada produk aktif. Jalankan ProductSeeder dulu.');
            return;
        }

        // Buat 10 transaksi
        for ($i = 0; $i < 10; $i++) {
            DB::transaction(function () use ($products) {
                // Status acak: PAID / UNPAID
                $status = Arr::random(['PAID', 'UNPAID']);

                // Buat dulu skeleton sale (id auto dari HasCustomId)
                $sale = Sales::create([
                    'status'         => $status,
                    'subtotal'       => 0,
                    'discount_total' => 0,
                    'tax_total'      => 0,
                    'grand_total'    => 0,
                    'paid_total'     => 0,
                    'change_due'     => 0,
                    'paid_at'        => null,
                ]);

                $itemsCount = random_int(2, 5);
                $picked = $products->random($itemsCount);

                $subtotal = 0.0;
                $discountTotal = 0.0;
                $taxTotal = 0.0;

                foreach ($picked as $p) {
                    $qty      = random_int(1, 5);
                    $price    = (float) $p->sell_price;
                    $taxRate  = (float) ($p->tax_rate ?? 0); // mis. 0 atau 11 (%)
                    // Diskon flat per item (acak kecil): 0 atau 1000
                    $discount = Arr::random([0, 0, 0, 1000]);

                    $lineBase   = ($price * $qty) - $discount;
                    $taxAmount  = round($lineBase * ($taxRate / 100), 2);
                    $lineTotal  = round($lineBase + $taxAmount, 2);

                    // Simpan item (id auto dari HasCustomId)
                    SaleItems::create([
                        'sale_id'    => $sale->id,
                        'product_id' => $p->id,
                        'qty'        => $qty,
                        'price'      => $price,
                        'tax_rate'   => $taxRate,
                        'discount'   => $discount,
                        'line_total' => $lineTotal,
                    ]);

                    $subtotal      += ($price * $qty);
                    $discountTotal += $discount;
                    $taxTotal      += $taxAmount;
                }

                $subtotal       = round($subtotal, 2);
                $discountTotal  = round($discountTotal, 2);
                $taxTotal       = round($taxTotal, 2);
                $grandTotal     = round($subtotal - $discountTotal + $taxTotal, 2);

                // Pembayaran
                $paidTotal = $status === 'PAID' ? $grandTotal : 0.0;
                $changeDue = 0.0; // bisa diisi kalau paidTotal > grandTotal

                $sale->update([
                    'subtotal'       => $subtotal,
                    'discount_total' => $discountTotal,
                    'tax_total'      => $taxTotal,
                    'grand_total'    => $grandTotal,
                    'paid_total'     => $paidTotal,
                    'change_due'     => $changeDue,
                    'paid_at'        => $status === 'PAID' ? Carbon::now() : null,
                ]);
            });
        }
    }
}
