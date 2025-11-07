<?php

namespace App\Filament\Operation\Resources\Internals\Deliveries\Tables;

use App\Models\Rider;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
                Action::make('assign_rider')
                    ->label('Assign')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->visible(fn ($record) => !$record->rider_id && in_array($record->status, ['pending']))
                    ->modalHeading('Assign Rider to Delivery')
                    ->modalDescription(fn ($record) => "Delivery: {$record->delivery_number}")
                    ->form([
                        Select::make('rider_id')
                            ->label('Select Rider')
                            ->options(function () {
                                return Rider::query()
                                    ->where('is_active', true)
                                    ->orderBy('first_name')
                                    ->get()
                                    ->mapWithKeys(fn ($rider) => [
                                        $rider->id => "{$rider->full_name} - {$rider->phone}"
                                    ]);
                            })
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->placeholder('Choose a rider')
                            ->helperText('Select an active rider for this delivery'),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            DB::transaction(function () use ($record, $data) {
                                $rider = Rider::findOrFail($data['rider_id']);
                                
                                $record->update([
                                    'rider_id' => $data['rider_id'],
                                    'status' => 'assigned',
                                    'assigned_at' => now(),
                                ]);

                                // Optional: Notify the rider
                                // $rider->notify(new DeliveryAssignedNotification($record));
                            });

                            Notification::make()
                                ->title('Rider Assigned')
                                ->body("Delivery {$record->delivery_number} assigned successfully.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Assignment Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('reassign_rider')
                    ->label('Reassign')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn ($record) => $record->rider_id && in_array($record->status, ['assigned', 'ready_for_pickup']))
                    ->modalHeading('Reassign Rider')
                    ->modalDescription(fn ($record) => "Current rider: {$record->rider->full_name}")
                    ->form([
                        Select::make('rider_id')
                            ->label('New Rider')
                            ->options(function () {
                                return Rider::query()
                                    ->where('is_active', true)
                                    ->orderBy('first_name')
                                    ->get()
                                    ->mapWithKeys(fn ($rider) => [
                                        $rider->id => "{$rider->full_name} - {$rider->phone}"
                                    ]);
                            })
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->helperText('This will change the assigned rider'),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $oldRider = $record->rider;
                            $newRider = Rider::findOrFail($data['rider_id']);

                            $record->update([
                                'rider_id' => $data['rider_id'],
                                'assigned_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Rider Reassigned')
                                ->body("Changed from {$oldRider->full_name} to {$newRider->full_name}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Reassignment Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('view_details')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->color('info'),
                    // ->url(fn ($record) => route('filament.operation.resources.deliveries.view', ['record' => $record->id]))
                    // ->openUrlInNewTab(false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_assign_rider')
                        ->label('Assign Rider')
                        ->icon('heroicon-o-user-group')
                        ->color('primary')
                        ->modalHeading('Bulk Assign Rider')
                        ->modalDescription('Assign the same rider to all selected deliveries')
                        ->form([
                            Select::make('rider_id')
                                ->label('Select Rider')
                                ->options(function () {
                                    return Rider::query()
                                        ->where('is_active', true)
                                        ->orderBy('first_name')
                                        ->get()
                                        ->mapWithKeys(fn ($rider) => [
                                            $rider->id => "{$rider->full_name} - {$rider->phone}"
                                        ]);
                                })
                                ->searchable()
                                ->required()
                                ->native(false)
                                ->helperText('All selected unassigned deliveries will be assigned to this rider'),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $assignedCount = 0;
                            $skippedCount = 0;

                            foreach ($records as $record) {
                                // Only assign if pending and unassigned
                                if (!$record->rider_id && $record->status === 'pending') {
                                    try {
                                        $record->update([
                                            'rider_id' => $data['rider_id'],
                                            'status' => 'assigned',
                                            'assigned_at' => now(),
                                        ]);
                                        $assignedCount++;
                                    } catch (\Exception $e) {
                                        $skippedCount++;
                                    }
                                } else {
                                    $skippedCount++;
                                }
                            }

                            if ($assignedCount > 0) {
                                $rider = Rider::find($data['rider_id']);
                                Notification::make()
                                    ->title('Bulk Assignment Complete')
                                    ->body("{$assignedCount} deliveries assigned to {$rider->full_name}." . 
                                          ($skippedCount > 0 ? " {$skippedCount} skipped (already assigned or not pending)." : ""))
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('No Assignments Made')
                                    ->body('Selected deliveries are already assigned or not in pending status.')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}