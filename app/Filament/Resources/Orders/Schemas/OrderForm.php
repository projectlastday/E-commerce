<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_number')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('PENDING_PAYMENT'),
                TextInput::make('total_amount')
                    ->required()
                    ->numeric(),
                TextInput::make('shipping_cost')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->prefix('$'),
                TextInput::make('shipping_address')
                    ->required(),
                TextInput::make('payment_method')
                    ->required(),
                TextInput::make('payment_reference'),
                DateTimePicker::make('paid_at'),
            ]);
    }
}
