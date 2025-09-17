<?php

namespace App\Filament\Resources\Physicians\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PhysiciansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()->label('Date')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('license_number')
                    ->searchable(),
                TextColumn::make('license_expiry_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('medical_council_registration')
                    ->searchable(),
                TextColumn::make('specialization')
                    ->searchable(),
                TextColumn::make('years_experience')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('qualification_level'),
                TextColumn::make('practice_name')
                    ->searchable(),
                TextColumn::make('county')
                    ->searchable(),
                TextColumn::make('city')
                    ->searchable(),
                TextColumn::make('practice_phone')
                    ->searchable(),
                TextColumn::make('practice_email')
                    ->searchable(),
                TextColumn::make('practice_type'),
                TextColumn::make('verification_status'),
                TextColumn::make('verified_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('commission_rate')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_commissions_earned')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_prescriptions')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_fulfilled_prescriptions')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('allow_generic_substitution')
                    ->boolean(),
                IconColumn::make('require_patient_consent')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->boolean(),
                IconColumn::make('accepting_prescriptions')
                    ->boolean(),
                TextColumn::make('practice_start_time')
                    ->time()
                    ->sortable(),
                TextColumn::make('practice_end_time')
                    ->time()
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
