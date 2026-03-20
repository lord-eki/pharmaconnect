<?php

namespace App\Filament\Operation\Resources\Patients\Tables;

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
                TextColumn::make('insurance_number')->placeholder('--')->searchable(),
                TextColumn::make('insuranceProvider.company_name')
                    ->label('Insurance Provider')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->getStateUsing(function ($record) {
                        if ($record->insuranceProvider) {
                            return $record->insuranceProvider->company_name;
                        }

                        if ($record->insurance_provider) {
                            if (is_numeric($record->insurance_provider)) {
                                $provider = \App\Models\InsuranceProvider::find((int) $record->insurance_provider);

                                return $provider?->company_name ?? $record->insurance_provider;
                            }
                            return $record->insurance_provider;
                        }

                        return '—';
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
