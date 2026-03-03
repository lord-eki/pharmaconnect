<?php

namespace App\Filament\Resources\Prescriptions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PrescriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('prescription_number')
                    ->searchable(),
                TextColumn::make('physician.name')
                    ->searchable(),
                TextColumn::make('patient.id')
                    ->searchable(),
                BadgeColumn::make('status')->colors([
                    'info' => 'processing',
                    'success' => 'fulfilled',
                    'danger' => 'cancelled',
                    'gray' => 'draft'
                ]),
                TextColumn::make('total_amount')->money('KES')
                    ->numeric(),
                IconColumn::make('insurance_covered')
                    ->boolean(),
                TextColumn::make('prescribed_at')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
           
                TextColumn::make('insuranceClaim.id')
                    ->searchable(),
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
