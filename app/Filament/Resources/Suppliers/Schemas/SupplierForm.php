<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('')->schema([
                Select::make('user_id')
                    ->options(function () {
                        return User::whereHas('roles', fn($q) => $q->where('name', 'Supplier'))
                            ->whereDoesntHave('supplier')
                            ->pluck('name', 'id');
                    })->label('Name'),
                TextInput::make('company_name')
                    ->required(),
                TextInput::make('registration_number')
                    ->required(),
                TextInput::make('license_number')
                    ->required(),
                TextInput::make('contact_person')
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                Textarea::make('address')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('county')
                    ->required(),
                TextInput::make('city')
                    ->required(),
                TextInput::make('postal_code')
                    ->default(null),
                TextInput::make('bank_account_name')
                    ->default(null),
                TextInput::make('bank_account_number')
                    ->default(null),
                TextInput::make('bank_name')
                    ->default(null),
                TextInput::make('bank_branch')
                    ->default(null),
                TextInput::make('tax_pin')
                    ->default(null),
                Toggle::make('is_verified')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('rating')
                    ->numeric()
                    ->default(null),
                TextInput::make('fulfillment_sla_hours')
                    ->required()
                    ->numeric()
                    ->default(24),
                ])->columnSpanFull()->columns(3)
            ]);
    }
}
