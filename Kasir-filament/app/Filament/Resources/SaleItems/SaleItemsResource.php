<?php

namespace App\Filament\Resources\SaleItems;

use App\Filament\Resources\SaleItems\Pages\CreateSaleItems;
use App\Filament\Resources\SaleItems\Pages\EditSaleItems;
use App\Filament\Resources\SaleItems\Pages\ListSaleItems;
use App\Filament\Resources\SaleItems\Schemas\SaleItemsForm;
use App\Filament\Resources\SaleItems\Tables\SaleItemsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\SaleItems;

class SaleItemsResource extends Resource
{
    protected static ?string $model = SaleItems::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'saleItems';

    public static function form(Schema $schema): Schema
    {
        return SaleItemsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SaleItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSaleItems::route('/'),
            'create' => CreateSaleItems::route('/create'),
            'edit' => EditSaleItems::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
