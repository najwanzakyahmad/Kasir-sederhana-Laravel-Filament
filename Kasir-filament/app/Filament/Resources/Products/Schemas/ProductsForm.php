<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;

class ProductsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('barcode')
                    ->label('Barcode')
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('name')
                    ->label('Product Name')
                    ->required()
                    ->maxLength(255),

                FileUpload::make('image')
                    ->label('Product Image')
                    ->image()
                    ->disk('public')
                    ->directory('products')
                    ->visibility('public')  
                    ->required(),

                TextInput::make('sell_price')
                    ->label('Sell Price')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),

                TextInput::make('cost_price')
                    ->label('Cost Price')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),

                TextInput::make('tax_rate')
                    ->label('Tax Rate (%)')
                    ->numeric()
                    ->suffix('%'),

                TextInput::make('qty_on_hand')
                    ->label('Stock')
                    ->numeric()
                    ->default(0)
                    ->required(),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
