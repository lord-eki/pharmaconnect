<?php

namespace App\Filament\Resources\Deliveries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DeliveriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                       TextColumn::make('created_at')
                    ->dateTime()->sortable()
                    ->label('Date'),
                TextColumn::make('delivery_number')
                    ->searchable(),
                TextColumn::make('order.id')
                    ->searchable()->label('Order'),
                TextColumn::make('rider.id')
                    ->searchable()->label('Rider'),
                TextColumn::make('pickup_latitude')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('pickup_longitude')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('delivery_latitude')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('delivery_longitude')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estimated_distance_km')
                    ->numeric()
                    ->sortable()->label('Distance (km)'),
                TextColumn::make('delivery_fee')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status'),
                TextColumn::make('actual_pickup')
                    ->dateTime()
                    ->sortable(),
           
                TextColumn::make('actual_delivery')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('recipient_name')
                    ->searchable(),
                TextColumn::make('recipient_phone')
                    ->searchable(),
                TextColumn::make('proof_of_delivery')
                    ->searchable(),
         
           
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
