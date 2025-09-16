<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\Pages;
use App\Filament\Resources\Sales\Schemas\SalesForm;
use App\Filament\Resources\Sales\Tables\SalesTable;
use App\Models\Sales;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables;
// (opsional) kalau mau pakai enum icon:
use Filament\Support\Icons\Heroicon;

class SalesResource extends Resource
{
    protected static ?string $model = Sales::class;

    // ✅ perbaikan tipe:
    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    // Opsi A (string klasik):
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    // Opsi B (enum, lebih “v4 way”):
    // protected static string|BackedEnum|null $navigationIcon = Heroicon::ReceiptPercent;

    protected static ?string $navigationLabel = 'Sales';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return SalesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSales::route('/'),
            'create' => Pages\CreateSales::route('/create'),
            'edit'   => Pages\EditSales::route('/{record}/edit'),
        ];
    }
}
