<?php

namespace App\Filament\Resources\Sales\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Image;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

use App\Forms\Components\IdrInput;
use App\Models\Products;

class SalesForm
{
    public static function configure(Schema $schema): Schema
    {
        // 1) BENERIN NAMA HELPER
        $toNumber = function ($v): float {
            if ($v === null || $v === '') return 0.0;
            $digits = preg_replace('/\D+/', '', (string) $v);

            return $digits === '' ? 0.0 : (float) $digits;
        };

        // 2) IMPORT HELPER KE DALAM CLOSURE DENGAN use ($toNumber)
        $recalcTotalsRoot = function (Get $get, Set $set) use ($toNumber): void {
            $items = $get('items') ?? [];

            $subtotal = 0.0;
            $discountTotal = 0.0;
            $taxTotal = 0.0;
            $grandTotal = 0.0;

            foreach ($items as $row) {
                $qty      = (int)   $toNumber($row['qty'] ?? 0);
                $price    =         $toNumber($row['price'] ?? 0);
                $discount =         $toNumber($row['discount'] ?? 0);
                $taxRate  =         $toNumber($row['tax_rate'] ?? 0);

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

            // pakai helper juga biar "6.500" ga kebaca 6.5
            $paid = $toNumber($get('paid_total') ?? 0);
            $set('change_due', round($paid - $grandTotal, 2));
        };

        // 3) IMPORT HELPER DI CLOSURE SATU LAGI
        $recalcFromInsideItem = function (Get $get, Set $set) use ($toNumber): void {
            $qty      = (int)   $toNumber($get('qty') ?? 0);
            $price    =         $toNumber($get('price') ?? 0);
            $discount =         $toNumber($get('discount') ?? 0);
            $taxRate  =         $toNumber($get('tax_rate') ?? 0);

            $base      = max($qty * $price, 0);
            $afterDisc = max($base - $discount, 0);
            $tax       = $afterDisc * ($taxRate / 100);
            $line      = $afterDisc + $tax;

            $set('line_total', round($line, 2));

            // Recalc total dari seluruh items (sanitize juga)
            $items = $get('../../items') ?? [];
            $subtotal = $discountTotal = $taxTotal = $grandTotal = 0.0;

            foreach ($items as $row) {
                $q  = (int)   $toNumber($row['qty'] ?? 0);
                $pr =         $toNumber($row['price'] ?? 0);
                $dc =         $toNumber($row['discount'] ?? 0);
                $tr =         $toNumber($row['tax_rate'] ?? 0);

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

            $paid = $toNumber($get('../../paid_total') ?? 0);
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
                        ->itemLabel(function (array $state): ?string {
                            if (blank($state['product_id'] ?? null)) return '';
                            return Products::find($state['product_id'])?->name ?: 'Item';
                        })
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, ?array $state) use ($recalcTotalsRoot) {
                            $recalcTotalsRoot($get, $set);
                        })
                        ->schema([
                            // Baris 1: Preview (1) vs Produk (4) — rasio 1:4
                            Grid::make(5)
                                ->columnSpanFull()
                                ->schema([
                                    Grid::make(1)
                                        ->columnSpan(1)
                                        ->schema([
                                            Text::make('Preview')
                                                ->color('neutral')
                                                ->extraAttributes(['class' => 'text-center mb-2']),
                                            Image::make(
                                                url: fn (Get $get) => ($id = $get('product_id')) && ($img = Products::find($id)?->image)
                                                    ? asset('storage/' . ltrim($img, '/'))
                                                    : 'data:image/gif;base64,R0lGODlhAQABAAAAACw=', // 1x1 transparan
                                                alt: 'Preview',
                                            )
                                                ->imageWidth('80px')
                                                ->imageHeight('80px')
                                                ->alignCenter(),
                                        ]),

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

                            // Baris 2+: input angka
                            TextInput::make('qty')
                                ->label('Qty')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required()
                                ->columnSpan(1)
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
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => $recalcFromInsideItem($get, $set)),

                            IdrInput::make('price')
                                ->label('Harga')
                                ->required()
                                ->columnSpan(1)
                                ->extraAttributes(['class' => 'w-full'])
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => $recalcFromInsideItem($get, $set)),

                            IdrInput::make('discount')
                                ->label('Diskon')
                                ->default(0)
                                // kosong => 0, selain itu parse angka
                                ->dehydrateStateUsing(function ($state) {
                                    if ($state === null || $state === '') return 0;
                                    return (float) preg_replace('/\D/', '', (string) $state);
                                })
                                ->columnSpan(1)
                                ->extraAttributes(['class' => 'w-full'])
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => $recalcFromInsideItem($get, $set)),

                            TextInput::make('tax_rate')
                                ->label('Pajak (%)')
                                ->numeric()
                                ->default(0)
                                ->columnSpan(1)
                                ->extraAttributes(['class' => 'w-full'])
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => $recalcFromInsideItem($get, $set)),

                            TextInput::make('line_total')
                                ->label('Total Baris')
                                ->disabled()
                                ->dehydrated()
                                ->columnSpan(1)
                                ->formatStateUsing(fn ($v) => $v === null ? null : 'Rp ' . number_format((float) $v, 0, ',', '.'))
                                ->extraAttributes(['class' => 'w-full']),
                        ]),
                ]),

            Section::make('Ringkasan & Pembayaran')
                ->columns(2)
                ->schema([
                    TextInput::make('subtotal')
                        ->label('Subtotal')
                        ->disabled()
                        ->dehydrated()
                        ->columnSpan(1)
                        ->formatStateUsing(fn ($v) => $v === null ? null : 'Rp ' . number_format((float) $v, 0, ',', '.')),

                    TextInput::make('discount_total')
                        ->label('Total Diskon')
                        ->disabled()
                        ->dehydrated()
                        ->columnSpan(1)
                        ->formatStateUsing(fn ($v) => $v === null ? null : 'Rp ' . number_format((float) $v, 0, ',', '.')),

                    TextInput::make('tax_total')
                        ->label('Total Pajak')
                        ->disabled()
                        ->dehydrated()
                        ->columnSpan(1)
                        ->formatStateUsing(fn ($v) => $v === null ? null : 'Rp ' . number_format((float) $v, 0, ',', '.')),

                    TextInput::make('grand_total')
                        ->label('Grand Total')
                        ->disabled()
                        ->dehydrated()
                        ->columnSpan(1)
                        ->formatStateUsing(fn ($v) => $v === null ? null : 'Rp ' . number_format((float) $v, 0, ',', '.')),

                    Select::make('status')
                        ->label('Status')
                        ->options(['draft' => 'Draft', 'paid' => 'Paid'])
                        ->default('paid')
                        ->native(false)
                        ->required()
                        ->columnSpan(1),

                    IdrInput::make('paid_total')
                        ->label('Dibayar')
                        ->default(0)
                        ->live() // boleh live realtime
                        ->afterStateUpdated(function (Get $get, Set $set, $state) use ($recalcTotalsRoot) {
                            // cukup hitung ulang saja, JANGAN auto-set ke grand total
                            $recalcTotalsRoot($get, $set);
                        })
                        ->rule(fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get, $toNumber) {
                            $gt   = (float) ($get('grand_total') ?? 0);
                            $paid = $toNumber($value ?? 0);
                            if ($paid < $gt) {
                                $fail('Pembayaran (Dibayar) harus ≥ Grand Total (Rp '.number_format($gt, 0, ',', '.').').');
                            }
                        })
                        ->helperText('Tidak boleh kurang dari Grand Total')
                        ->columnSpan(1),

                    TextInput::make('change_due')
                        ->label('Kembalian')
                        ->disabled()
                        ->dehydrated()
                        ->columnSpan(1)
                        ->formatStateUsing(fn ($v) => $v === null ? null : 'Rp ' . number_format((float) $v, 0, ',', '.')),

                    Toggle::make('auto_set_paid_at')
                        ->label('Isi tanggal bayar saat Status = Paid')
                        ->default(true)
                        ->columnSpan(1),
                ]),
        ]);
    }
}
