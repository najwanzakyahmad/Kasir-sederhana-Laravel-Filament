<?php

namespace App\Services;

use App\Models\Sales;
use App\Models\Products;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    public static function applyForSale(Sales $sale): void
    {
        DB::transaction(function () use ($sale) {
            $sale->loadMissing('items');

            foreach ($sale->items as $item) {
                $product = Products::whereKey($item->product_id)->lockForUpdate()->first();
                if (! $product) continue;

                if ($product->qty_on_hand < $item->qty) {
                    throw new RuntimeException("Stok {$product->name} kurang (sisa {$product->qty_on_hand}).");
                }

                $product->decrement('qty_on_hand', $item->qty);
            }

            $sale->forceFill(['stock_applied' => true])->saveQuietly();
        });
    }

    public static function revertForSale(Sales $sale): void
    {
        DB::transaction(function () use ($sale) {
            $sale->loadMissing('items');

            foreach ($sale->items as $item) {
                $product = Products::whereKey($item->product_id)->lockForUpdate()->first();
                if (! $product) continue;

                $product->increment('qty_on_hand', $item->qty);
            }

            $sale->forceFill(['stock_applied' => false])->saveQuietly();
        });
    }
}
