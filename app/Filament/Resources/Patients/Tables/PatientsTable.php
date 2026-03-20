<?php

namespace App\Filament\Resources\Patients\Tables;

use App\Models\InsuranceProvider;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PatientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Date'),
                TextColumn::make('first_name')->searchable(),
                TextColumn::make('last_name')->searchable(),
                TextColumn::make('phone'),
                TextColumn::make('email')->searchable(),
                TextColumn::make('county')->searchable(),
                TextColumn::make('insurance_number')->searchable()->placeholder('--'),
                TextColumn::make('insurance_provider')->searchable()->placeholder('--')->formatStateUsing(function ($state) {
                    if (is_numeric($state)){
                        $provider = InsuranceProvider::find($state);
                        return $provider ? $provider->company_name : $state;
                    } 

                    return $state;
                }),

            ])
            ->filters([
                //
            ])
            ->recordActions([
            ])
            ->toolbarActions([

            ]);
    }
}
