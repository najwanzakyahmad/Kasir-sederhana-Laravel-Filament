<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SalesResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\InventoryService;

class CreateSales extends CreateRecord
{
    protected static string $resource = SalesResource::class;

    protected function afterCreate(): void
    {
        // Items sudah tersimpan, sekarang apply stok jika status = paid
        $sale = $this->record->refresh()->loadMissing('items');

        if ($sale->status === 'paid' && ! $sale->stock_applied) {
            InventoryService::applyForSale($sale);
        }
    }
}
