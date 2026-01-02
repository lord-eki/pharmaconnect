<?php

namespace App\Filament\Resources\Deliveries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DeliveriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                       TextColumn::make('created_at')
                    ->date()->sortable()
                    ->label('Date'),
                TextColumn::make('delivery_number')
                    ->searchable(),
                TextColumn::make('order.id')
                    ->searchable()->label('Order'),
                TextColumn::make('rider.last_name')
                    ->searchable()->label('Rider'),
              
        
               
                BadgeColumn::make('status')->colors([
                        'success' => 'delivered',
                        'warning' => 'pending',
                        'info' => 'picked_up',
                        'danger' => 'cancelled',
                        'gray' =>'assigned'
                        
                    ]),
           
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
                // EditAction::make(),
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
