<?php

namespace App\Filament\Resources\Sales\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;
use App\Models\Products;

class SalesForm
{
    public static function configure(Schema $schema): Schema
    {
        // Hitung total dari ROOT (dipanggil saat array items berubah / paid_total berubah)
        $recalcTotalsRoot = function (Get $get, Set $set): void {
            $items = $get('items') ?? [];

            $subtotal = 0.0;
            $discountTotal = 0.0;
            $taxTotal = 0.0;
            $grandTotal = 0.0;

            foreach ($items as $row) {
                $qty      = (int)   ($row['qty'] ?? 0);
                $price    = (float) ($row['price'] ?? 0);
                $discount = (float) ($row['discount'] ?? 0);
                $taxRate  = (float) ($row['tax_rate'] ?? 0);

                $base      = max($qty * $price, 0);
                $afterDisc = max($base - $discount, 0);
                $tax       = $afterDisc * ($taxRate / 100);
                $line      = $afterDisc + $tax;

                $subtotal      += $base;
                $discountTotal += $discount;
                $taxTotal      += $tax;
                $grandTotal    += $line;
            }

            $set('subtotal', round($subtotal, 2));
            $set('discount_total', round($discountTotal, 2));
            $set('tax_total', round($taxTotal, 2));
            $set('grand_total', round($grandTotal, 2));

            $paid = (float) ($get('paid_total') ?? 0);
            $set('change_due', round($paid - $grandTotal, 2));
        };

        // Hitung ulang dari DALAM item (pakai path relatif "../../")
        $recalcFromInsideItem = function (Get $get, Set $set): void {
            $qty      = (int)   ($get('qty') ?? 0);
            $price    = (float) ($get('price') ?? 0);
            $discount = (float) ($get('discount') ?? 0);
            $taxRate  = (float) ($get('tax_rate') ?? 0);

            $base      = max($qty * $price, 0);
            $afterDisc = max($base - $discount, 0);
            $tax       = $afterDisc * ($taxRate / 100);
            $line      = $afterDisc + $tax;

            $set('line_total', round($line, 2));

            $items = $get('../../items') ?? [];

            $subtotal = 0.0;
            $discountTotal = 0.0;
            $taxTotal = 0.0;
            $grandTotal = 0.0;

            foreach ($items as $row) {
                $q  = (int)   ($row['qty'] ?? 0);
                $pr = (float) ($row['price'] ?? 0);
                $dc = (float) ($row['discount'] ?? 0);
                $tr = (float) ($row['tax_rate'] ?? 0);

                $b  = max($q * $pr, 0);
                $ad = max($b - $dc, 0);
                $tx = $ad * ($tr / 100);
                $ln = $ad + $tx;

                $subtotal      += $b;
                $discountTotal += $dc;
                $taxTotal      += $tx;
                $grandTotal    += $ln;
            }

            $set('../../subtotal', round($subtotal, 2));
            $set('../../discount_total', round($discountTotal, 2));
            $set('../../tax_total', round($taxTotal, 2));
            $set('../../grand_total', round($grandTotal, 2));

            $paid = (float) ($get('../../paid_total') ?? 0);
            $set('../../change_due', round($paid - $grandTotal, 2));
        };

        return $schema->components([
            Section::make('Barang')
                ->columns(1)
                ->schema([
                    Repeater::make('items')
                        ->relationship('items')
                        ->columnSpanFull()
                        ->minItems(1)
                        ->defaultItems(1)
                        ->reorderable(false)
                        ->collapsed()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, ?array $state) use ($recalcTotalsRoot) {
                            $recalcTotalsRoot($get, $set);
                        })
                        ->schema([
                            // === Baris 1: Gambar vs Produk (rasio 1:4) ===
                            Grid::make(5)
                                ->columnSpanFull()
                                ->schema([
                                    // Gambar (1/5)
                                    Placeholder::make('product_preview')
                                        ->label(' ')
                                        ->columnSpan(1)
                                        ->content(function (Get $get) {
                                            $productId = $get('product_id');
                                            if (! $productId) return '';
                                            $p = Products::find($productId);
                                            if (! $p || ! $p->image) return '';
                                            $url = asset('storage/' . ltrim($p->image, '/'));
                                            // FIXED size 80x80, tetap rapi dengan object-fit
                                            return new HtmlString(
                                                '<div style="display:flex;align-items:center;justify-content:center;width:100%;">' .
                                                    '<img src="' . e($url) . '" alt="preview" ' .
                                                    'style="width:80px;height:80px;object-fit:cover;border-radius:12px;display:block;" />' .
                                                '</div>'
                                            );
                                        }),

                                    // Select Produk (4/5)
                                    Select::make('product_id')
                                        ->label('Produk')
                                        ->native(false)
                                        ->searchable()
                                        ->preload()
                                        ->columnSpan(4)
                                        ->required()
                                        ->getSearchResultsUsing(function (string $search) {
                                            return Products::query()
                                                ->where('is_active', true)
                                                ->where(function ($q) use ($search) {
                                                    $q->where('name', 'like', "%{$search}%")
                                                      ->orWhere('barcode', 'like', "%{$search}%");
                                                })
                                                ->orderBy('name')
                                                ->limit(50)
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        })
                                        ->getOptionLabelUsing(fn ($value) => $value ? optional(Products::find($value))->name : null)
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) use ($recalcFromInsideItem) {
                                            if (! $state) return;
                                            $p = Products::find($state);
                                            if ($p) {
                                                $set('price', (float) $p->sell_price);
                                                $set('tax_rate', (float) $p->tax_rate);
                                                if ((int) ($get('qty') ?? 0) === 0) {
                                                    $set('qty', 1);
                                                }
                                            }
                                            $recalcFromInsideItem($get, $set);
                                        }),
                                ]),

                            // === Baris 2 dst: field angka dalam grid 12 kolom ===
                            Grid::make(12)
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('qty')
                                        ->label('Qty')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->required()
                                        ->columnSpan(2)
                                        ->extraAttributes(['class' => 'w-full'])
                                        ->rule(function (Get $get) {
                                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                $productId = $get('product_id');
                                                if (! $productId) return;
                                                $stock = (int) (Products::find($productId)?->qty_on_hand ?? 0);
                                                if ((int) $value > $stock) {
                                                    $fail("Stok tersisa {$stock}.");
                                                }
                                            };
                                        })
                                        ->live()
                                        ->afterStateUpdated(fn (Get $get, Set $set) => $recalcFromInsideItem($get, $set)),

                                    TextInput::make('price')
                                        ->label('Harga')
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->required()
                                        ->columnSpan(3)
                                        ->extraAttributes(['class' => 'w-full'])
                                        ->live()
                                        ->afterStateUpdated(fn (Get $get, Set $set) => $recalcFromInsideItem($get, $set)),

                                    TextInput::make('discount')
                                        ->label('Diskon')
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->nullable() // boleh kosong (NULL)
                                        ->columnSpan(3)
                                        ->extraAttributes(['class' => 'w-full'])
                                        ->live()
                                        ->afterStateUpdated(fn (Get $get, Set $set) => $recalcFromInsideItem($get, $set)),

                                    TextInput::make('tax_rate')
                                        ->label('Pajak (%)')
                                        ->numeric()
                                        ->default(0)
                                        ->columnSpan(2)
                                        ->extraAttributes(['class' => 'w-full'])
                                        ->live()
                                        ->afterStateUpdated(fn (Get $get, Set $set) => $recalcFromInsideItem($get, $set)),

                                    TextInput::make('line_total')
                                        ->label('Total Baris')
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->disabled()
                                        ->dehydrated()
                                        ->columnSpan(2)
                                        ->extraAttributes(['class' => 'w-full']),
                                ]),
                        ]),
                ]),

            Section::make('Ringkasan & Pembayaran')
                ->columns(12)
                ->schema([
                    TextInput::make('subtotal')->label('Subtotal')->numeric()->prefix('Rp')->disabled()->dehydrated()->columnSpan(3),
                    TextInput::make('discount_total')->label('Total Diskon')->numeric()->prefix('Rp')->disabled()->dehydrated()->columnSpan(3),
                    TextInput::make('tax_total')->label('Total Pajak')->numeric()->prefix('Rp')->disabled()->dehydrated()->columnSpan(3),
                    TextInput::make('grand_total')->label('Grand Total')->numeric()->prefix('Rp')->disabled()->dehydrated()->columnSpan(3),

                    Select::make('status')
                        ->label('Status')
                        ->options(['draft' => 'Draft', 'paid' => 'Paid'])
                        ->default('paid')
                        ->native(false)
                        ->required()
                        ->columnSpan(3),

                    TextInput::make('paid_total')
                        ->label('Dibayar')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => $recalcTotalsRoot($get, $set))
                        ->columnSpan(3),

                    TextInput::make('change_due')->label('Kembalian')->numeric()->prefix('Rp')->disabled()->dehydrated()->columnSpan(3),

                    Toggle::make('auto_set_paid_at')->label('Isi tanggal bayar saat Status = Paid')->default(true)->columnSpan(3),
                ]),
        ]);
    }
}
