<?php

namespace App\Filament\Resources\Quotations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([ 
                TextColumn::make('created_at')
                    ->dateTime()->label('Date')
                    ->sortable(),
                TextColumn::make('quotation_number')
                    ->searchable(),
                TextColumn::make('prescription.prescription_number')
                    ->searchable(),
                TextColumn::make('total_amount')
                    ->numeric()->money('KES')
                    ->sortable(),
                BadgeColumn::make('status')->colors([
                    'success' => 'sent',
                    'info' => 'accepted',
                    'danger' => 'rejected',
                    'gray' => 'pending',
                ]),
                TextColumn::make('valid_until')
                    ->dateTime()
                    ->sortable(),
              
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                ]),
            ]);
    }
}
