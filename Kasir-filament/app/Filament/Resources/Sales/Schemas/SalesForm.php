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
use Filament\Forms\Components\Hidden;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

use App\Forms\Components\IdrInput;
use App\Models\Products;

class SalesForm
{
    public static function configure(Schema $schema): Schema
    {
        // Helper: normalisasi nilai id (scalar) dari kemungkinan array state
        $normId = function ($v) {
            if (is_array($v)) {
                if (array_key_exists('id', $v)) {
                    return $v['id'];
                }
                foreach ($v as $vv) {
                    if (is_scalar($vv)) {
                        return $vv;
                    }
                }
                return null;
            }
            return $v;
        };

        // Parser "id-ID" -> float
        $toNumber = function ($v): float {
            if ($v === null || $v === '') return 0.0;
            $s = str_replace(['Rp', ' ', "\xC2\xA0"], '', (string) $v);
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
            $s = preg_replace('/[^0-9.]/', '', $s);
            $parts = explode('.', $s);
            if (count($parts) > 2) $s = $parts[0] . '.' . $parts[1];
            return (float) $s;
        };

        // Formatter tampilan Rp
        $fmt = fn ($v) => 'Rp ' . number_format((float) $v, 2, ',', '.');

        // Hitung ulang dari root
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

            $paid = $toNumber($get('paid_total') ?? 0);
            $set('change_due', round($paid - $grandTotal, 2));
        };

        // Hitung ulang dari dalam repeater item
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

            // Recalc total dari seluruh items
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
                        ->itemLabel(function (array $state) use ($normId): ?string {
                            $id = $normId($state['product_id'] ?? null);
                            if (blank($id)) return '';
                            return Products::find($id)?->name ?: 'Item';
                        })
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, ?array $state) use ($recalcTotalsRoot) {
                            $recalcTotalsRoot($get, $set);
                        })
                        ->schema([
                            // Baris 1: Preview (1) vs Produk (4)
                            Grid::make(5)
                                ->columnSpanFull()
                                ->schema([
                                    Grid::make(1)
                                        ->columnSpan(1)
                                        ->schema([
                                            Text::make('preview_label')
                                                ->content('Preview')
                                                ->color('neutral')
                                                ->extraAttributes(['class' => 'text-center mb-2']),
                                            Image::make(
                                                url: fn (Get $get) =>
                                                    ($id = $normId($get('product_id'))) &&
                                                    ($img = Products::find($id)?->image)
                                                        ? asset('storage/' . ltrim($img, '/'))
                                                        : 'data:image/gif;base64,R0lGODlhAQABAAAAACw=',
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
                                        ->getOptionLabelUsing(function ($value) use ($normId) {
                                            $id = $normId($value);
                                            return $id ? optional(Products::find($id))->name : null;
                                        })
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) use ($recalcFromInsideItem, $normId) {
                                            $id = $normId($state);
                                            if (! $id) return;

                                            $p = Products::find($id);
                                            if ($p) {
                                                // SET nilai terformat agar langsung muncul titik/koma di UI
                                                $set('price', number_format((float) $p->sell_price, 2, ',', '.'));
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

                            // DISPLAY line_total (IDR) + simpan hidden
                            Grid::make(1)
                                ->columnSpan(1)
                                ->schema([
                                    Text::make('line_total_label')
                                        ->content('Total Baris')
                                        ->color('neutral'),
                                    Text::make('line_total_display')
                                        ->content(fn (Get $get) => $fmt($get('line_total') ?? 0))
                                        ->extraAttributes(['class' => 'w-full']),
                                    Hidden::make('line_total')->dehydrated(),
                                ]),
                        ]),
                ]),

            Section::make('Ringkasan & Pembayaran')
                ->columns(2)
                ->schema([
                    // DISPLAY subtotal + hidden
                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema([
                            Text::make('subtotal_label')->content('Subtotal')->color('neutral'),
                            Text::make('subtotal_display')->content(fn (Get $get) => $fmt($get('subtotal') ?? 0)),
                            Hidden::make('subtotal')->dehydrated(),
                        ]),

                    // DISPLAY discount_total + hidden
                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema([
                            Text::make('discount_total_label')->content('Total Diskon')->color('neutral'),
                            Text::make('discount_total_display')->content(fn (Get $get) => $fmt($get('discount_total') ?? 0)),
                            Hidden::make('discount_total')->dehydrated(),
                        ]),

                    // DISPLAY tax_total + hidden
                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema([
                            Text::make('tax_total_label')->content('Total Pajak')->color('neutral'),
                            Text::make('tax_total_display')->content(fn (Get $get) => $fmt($get('tax_total') ?? 0)),
                            Hidden::make('tax_total')->dehydrated(),
                        ]),

                    // DISPLAY grand_total + hidden
                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema([
                            Text::make('grand_total_label')->content('Grand Total')->color('neutral'),
                            Text::make('grand_total_display')->content(fn (Get $get) => $fmt($get('grand_total') ?? 0)),
                            Hidden::make('grand_total')->dehydrated(),
                        ]),

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
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, $state) use ($recalcTotalsRoot) {
                            $recalcTotalsRoot($get, $set);
                        })
                        ->rule(fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get, $toNumber) {
                            $gt   = (float) ($get('grand_total') ?? 0);
                            $paid = $toNumber($value ?? 0);
                            if ($paid < $gt) {
                                $fail('Pembayaran (Dibayar) harus ≥ Grand Total (Rp ' . number_format($gt, 0, ',', '.') . ').');
                            }
                        })
                        ->helperText('Tidak boleh kurang dari Grand Total')
                        ->columnSpan(1),

                    // DISPLAY change_due + hidden
                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema([
                            Text::make('change_due_label')->content('Kembalian')->color('neutral'),
                            Text::make('change_due_display')->content(fn (Get $get) => $fmt($get('change_due') ?? 0)),
                            Hidden::make('change_due')->dehydrated(),
                        ]),
                ]),
        ]);
    }
}
