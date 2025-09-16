<?php

namespace App\Filament\Resources\Sales\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Invoice')
                    ->searchable()
                    ->copyable()
                    ->sortable()
                    ->copyMessage('Disalin!'),

                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (string $state) => strtoupper($state)),

                TextColumn::make('grand_total')
                    ->label('Grand Total')
                    ->alignRight()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.')),

                TextColumn::make('paid_total')
                    ->label('Dibayar')
                    ->alignRight()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.')),

                TextColumn::make('change_due')
                    ->label('Kembalian')
                    ->alignRight()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.')),

                TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->sortable()
                    ->dateTime('d M Y H:i'),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->sortable()
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
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
