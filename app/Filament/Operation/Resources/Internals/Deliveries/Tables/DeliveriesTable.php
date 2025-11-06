<?php

namespace App\Filament\Operation\Resources\Internals\Deliveries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DeliveriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('delivery_number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('order.order_number')
                    ->searchable()
                    ->sortable(),
                    // ->url(fn ($record) => route('filament.operations.resources.orders.view', ['record' => $record->order_id]))
                    // ->openUrlInNewTab(),

                TextColumn::make('rider.full_name')
                    ->searchable()
                    ->sortable()
                    ->default('Not Assigned')
                    ->badge()
                    ->color(fn ($state) => $state === 'Not Assigned' ? 'danger' : 'success'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'assigned' => 'info',
                        'ready_for_pickup' => 'warning',
                        'picked_up' => 'primary',
                        'in_transit' => 'primary',
                        'delivered' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('delivery_address')
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('scheduled_pickup')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                TextColumn::make('estimated_delivery')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                TextColumn::make('delivery_fee')
                    ->money('KES')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'assigned' => 'Assigned',
                        'ready_for_pickup' => 'Ready for Pickup',
                        'picked_up' => 'Picked Up',
                        'in_transit' => 'In Transit',
                        'delivered' => 'Delivered',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('rider')
                    ->relationship('rider', 'last_name')
                    ->searchable()
                    ->preload(),

                Filter::make('unassigned')
                    ->query(fn (Builder $query): Builder => $query->whereNull('rider_id'))
                    ->label('Unassigned Only')
                    ->toggle(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])->defaultSort('created_at', 'desc');
    }
}
