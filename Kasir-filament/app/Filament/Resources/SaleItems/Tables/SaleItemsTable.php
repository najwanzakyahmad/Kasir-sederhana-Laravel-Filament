<?php

namespace App\Filament\Resources\SaleItems\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;

class SaleItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sale.id')
                    ->label('Sale ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('qty')
                    ->label('Quantity')
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Price')
                    ->money('idr', true) // auto format jadi IDR
                    ->sortable(),

                TextColumn::make('tax_rate')
                    ->label('Tax Rate (%)')
                    ->sortable(),

                TextColumn::make('discount')
                    ->label('Discount')
                    ->money('idr', true)
                    ->sortable(),

                TextColumn::make('line_total')
                    ->label('Line Total')
                    ->money('idr', true)
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
