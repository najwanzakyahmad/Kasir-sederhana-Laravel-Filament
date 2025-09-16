<?php

namespace App\Filament\Resources\SaleItems\Pages;

use App\Filament\Resources\SaleItems\SaleItemsResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditSaleItems extends EditRecord
{
    protected static string $resource = SaleItemsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
