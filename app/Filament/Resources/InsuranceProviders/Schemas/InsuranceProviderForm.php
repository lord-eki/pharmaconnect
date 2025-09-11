<?php

namespace App\Filament\Resources\InsuranceProviders\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class InsuranceProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')->options(function () {
                    return User::whereHas('roles', fn($q) => $q->where('name', 'Insurer'))
                        ->whereDoesntHave('insuranceProvider')
                        ->pluck('name', 'id');
                })->required(),
                TextInput::make('company_name')
                    ->required(),
                TextInput::make('registration_number')
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
                TextInput::make('website')
                    ->default(null),
                Toggle::make('is_active')
                    ->required(),
                
            ]);
    }
}
