<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;

class ProductsTable
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

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('name')
                    ->label('Product Name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('barcode')
                    ->label('Barcode')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                ImageColumn::make('image')
                    ->label('Image')
                    ->square()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('sell_price')
                    ->label('Sell Price')
                    ->sortable()
                    ->money('idr', true)
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('cost_price')
                    ->label('Cost Price')
                    ->money('idr', true)
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('tax_rate')
                    ->label('Tax Rate (%)')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('qty_on_hand')
                    ->label('Stock')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit'),
                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Delete')
                    ->requiresConfirmation(),
                RestoreAction::make()
                    ->label('')
                    ->iconButton()
                    ->tooltip('Restore')
                    ->visible(fn ($record) => $record->trashed()),
                ForceDeleteAction::make()
                    ->label('')
                    ->iconButton()
                    ->tooltip('Delete Permanently')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->trashed()),
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
