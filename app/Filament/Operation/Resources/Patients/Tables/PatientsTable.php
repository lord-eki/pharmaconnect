<?php

namespace App\Filament\Operation\Resources\Patients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                TextColumn::make('insurance_provider')->searchable()->getStateUsing(fn($record) => $record->insuranceProvider?->company_name ?? $record->insurance_provider ?? '--' )

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
