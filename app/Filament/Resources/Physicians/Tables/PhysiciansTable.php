<?php

namespace App\Filament\Resources\Physicians\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                BadgeColumn::make('verification_status')->label('Status'),
              
                TextColumn::make('commission_rate')
                    ->numeric()->label('Commission(%) ')
                    ->sortable(),
                TextColumn::make('prescriptions_count')->counts('prescriptions')
                    ->numeric()->label('Total Presc.')
                    ->sortable(),
                // TextColumn::make('prescriptions_count')->label('FUlfilled')->counts(['prescriptions' =>  fn(Builder $query) => $query->where('status', 'fulfilled')])
                //     ->numeric()
                //     ->sortable(),
            
             
                

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
