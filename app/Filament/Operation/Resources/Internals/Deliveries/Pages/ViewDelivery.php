<?php

namespace App\Filament\Operation\Resources\Internals\Deliveries\Pages;

use App\Filament\Operation\Resources\Internals\Deliveries\DeliveryResource;
use App\Models\Rider;
use App\Notifications\CommissionEarnedNotification;
use App\Notifications\DeliveryAssignedNotification;
use App\Notifications\DeliveryStatusUpdatedNotification;
use App\Notifications\RiderReassignedNotification;
use App\Services\DeliveryTrackingService;
use App\Services\OrderFulfillmentService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;

class ViewDelivery extends ViewRecord
{
    protected static string $resource = DeliveryResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Delivery Overview')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('delivery_number')
                                    ->label('Delivery Number')
                                    ->weight('bold'),

                                TextEntry::make('status')
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

                                TextEntry::make('orders_count')
                                    ->label('Total Orders')
                                    ->state(fn ($record) => $record->orders->count())
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('delivery_fee')
                                    ->money('KES'),
                            ]),
                    ]),

                Section::make('Prescription Details')
                    ->schema([
                        TextEntry::make('prescription.prescription_number')
                            ->label('Prescription Number')
                            ->color(Color::Blue)
                            ->weight('bold'),

                        TextEntry::make('prescription.physician.name')
                            ->label('Physician'),

                        TextEntry::make('prescription.patient.full_name')
                            ->label('Patient'),

                        TextEntry::make('total_amount')
                            ->label('Total Orders Amount')
                            ->state(fn ($record) => $record->orders->sum('total_amount'))
                            ->money('KES')
                            ->weight('bold'),
                    ])
                    ->columns(4),

                Section::make('Orders')
                    ->schema([
                        RepeatableEntry::make('orders')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('order_number')
                                            ->label('Order Number')
                                            ->weight('bold'),

                                        TextEntry::make('supplier.company_name')
                                            ->label('Supplier'),

                                        TextEntry::make('status')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'pending_review' => 'warning',
                                                'sent_to_supplier' => 'info',
                                                'confirmed' => 'success',
                                                'delivered' => 'success',
                                                'cancelled' => 'danger',
                                                default => 'gray',
                                            }),

                                        TextEntry::make('total_amount')
                                            ->money('KES')
                                            ->weight('bold'),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('pivot.pickup_status')
                                            ->label('Pickup Status')
                                            ->badge()
                                            ->color(fn ($state): string => match ($state) {
                                                'picked_up' => 'success',
                                                'pending' => 'warning',
                                                'failed' => 'danger',
                                                default => 'gray',
                                            })
                                            ->formatStateUsing(fn ($state): string => ucfirst($state ?? 'pending')),

                                        TextEntry::make('pivot.picked_up_at')
                                            ->label('Picked Up At')
                                            ->dateTime('M d, Y H:i')
                                            ->placeholder('Not picked up yet'),
                                    ]),
                            ])
                            ->columns(1),
                    ])
                    ->collapsible(),

                Section::make('Rider Information')
                    ->schema([
                        TextEntry::make('rider.full_name')
                            ->label('Rider Name')
                            ->default('Not Assigned')
                            ->badge()
                            ->color(fn ($state) => $state === 'Not Assigned' ? 'danger' : 'success'),

                        TextEntry::make('rider.phone')
                            ->label('Phone')
                            ->default('N/A'),

                        TextEntry::make('rider.vehicle_type')
                            ->label('Vehicle')
                            ->default('N/A'),

                        TextEntry::make('rider.vehicle_registration')
                            ->label('Registration')
                            ->default('N/A'),

                        TextEntry::make('rider.rating')
                            ->label('Rating')
                            ->default('N/A')
                            ->suffix(' / 5'),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record->rider_id !== null),

                Section::make('Pickup Details')
                    ->schema([
                        TextEntry::make('pickup_address')
                            ->columnSpanFull(),

                        TextEntry::make('scheduled_pickup')
                            ->dateTime('M d, Y H:i A'),

                        TextEntry::make('actual_pickup')
                            ->dateTime('M d, Y H:i A')
                            ->placeholder('Not picked up yet'),
                    ])
                    ->columns(2),

                Section::make('Delivery Details')
                    ->schema([
                        TextEntry::make('delivery_address')
                            ->columnSpanFull(),

                        TextEntry::make('recipient_name'),

                        TextEntry::make('recipient_phone'),

                        TextEntry::make('estimated_delivery')
                            ->dateTime('M d, Y H:i A'),

                        TextEntry::make('actual_delivery')
                            ->dateTime('M d, Y H:i A')
                            ->placeholder('Not delivered yet'),

                        TextEntry::make('estimated_distance_km')
                            ->label('Distance')
                            ->suffix(' km'),

                        TextEntry::make('delivery_notes')
                            ->columnSpanFull()
                            ->placeholder('No notes'),
                    ])
                    ->columns(3),

                Section::make('Tracking')
                    ->schema([
                        TextEntry::make('tracking_count')
                            ->label('Tracking Points')
                            ->state(fn ($record) => $record->tracking()->count()),

                        TextEntry::make('current_location')
                            ->label('Last Known Location')
                            ->state(function ($record) {
                                $service = app(DeliveryTrackingService::class);
                                $location = $service->getCurrentLocation($record);

                                return $location ?
                                    "Lat: {$location['lat']}, Lng: {$location['lng']} ({$location['time_ago']})" :
                                    'No tracking data';
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => in_array($record->status, ['picked_up', 'in_transit', 'delivered'])),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            // Assign Rider 
            Action::make('assign_rider')
                ->label('Assign Rider')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->visible(fn ($record) => $record->status === 'pending' && ! $record->rider_id)
                ->form([
                    Select::make('rider_id')
                        ->label('Select Rider')
                        ->options(
                            Rider::available()
                                ->available()
                                ->get()
                                ->mapWithKeys(fn ($rider) => [$rider->id => "{$rider->full_name} - {$rider->phone}"])
                        )
                        ->required()
                        ->searchable()
                        ->helperText('Only available riders are shown'),

                   TextInput::make('delivery_fee')
                        ->label('Delivery Fee (KES)')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->prefix('KES')
                        ->helperText('Enter the delivery fee for this order'),
                ])
                ->action(function ($record, array $data) {
                    try {
                        $rider = Rider::findOrFail($data['rider_id']);

                        $record->update([
                            'rider_id' => $data['rider_id'],
                            'status' => 'assigned',
                            'delivery_fee' => $data['delivery_fee'],
                        ]);

                        $rider->update(['is_available' => false]);

                        if ($rider->user) {
                            $rider->user->notify(new DeliveryAssignedNotification($record));
                        }

                        Notification::make()
                            ->success()
                            ->title('Rider Assigned')
                            ->body("Rider {$rider->full_name} has been assigned to this delivery.")
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            // Reassign Rider
            Action::make('reassign_rider')
                ->label('Reassign Rider')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn ($record) => $record->rider_id && ! in_array($record->status, ['delivered', 'cancelled']))
                ->form([
                    Select::make('rider_id')
                        ->label('Select New Rider')
                        ->options(
                            Rider::active()
                                ->available()
                                ->get()
                                ->mapWithKeys(fn ($rider) => [$rider->id => "{$rider->full_name} - {$rider->phone}"])
                        )
                        ->required()
                        ->searchable(),

                    \Filament\Forms\Components\TextInput::make('delivery_fee')
                        ->label('Delivery Fee (KES)')
                        ->numeric()
                        ->minValue(0)
                        ->default(fn ($record) => $record->delivery_fee)
                        ->required()
                        ->prefix('KES')
                        ->helperText('Update the delivery fee if needed'),

                    Textarea::make('reason')
                        ->label('Reason for Reassignment')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function ($record, array $data) {
                    try {
                        $oldRider = $record->rider;
                        $newRider = Rider::findOrFail($data['rider_id']);

                        if ($oldRider) {
                            $oldRider->update(['is_available' => true]);
                            if ($oldRider->user) {
                                $oldRider->user->notify(new DeliveryStatusUpdatedNotification($record, 'assigned', 'reassigned'));
                            }
                        }

                        $record->update([
                            'rider_id' => $data['rider_id'],
                            'delivery_fee' => $data['delivery_fee'],
                            'delivery_notes' => ($record->delivery_notes ?? '').
                                "\n\nRider reassigned: ".now()->toDateTimeString().
                                "\nReason: ".$data['reason'],
                        ]);

                        $newRider->update(['is_available' => false]);

                        if ($newRider->user) {
                            $newRider->user->notify(new RiderReassignedNotification($record, $data['reason']));
                        }

                        Notification::make()
                            ->success()
                            ->title('Rider Reassigned')
                            ->body("New rider {$newRider->full_name} has been assigned.")
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('mark_picked_up')
                ->label('Mark Picked Up')
                ->icon('heroicon-o-truck')
                ->color('info')
                ->visible(fn ($record) => in_array($record->status, ['assigned']))
                ->requiresConfirmation()
                ->action(function ($record) {
                    try {
                        // Check if all orders are picked up
                        if ($record->allOrdersPickedUp()) {
                            $record->update([
                                'status' => 'in_transit',
                                'actual_pickup' => now(),
                            ]);
                        } else {
                            $record->update(['status' => 'picked_up']);
                        }

                        if ($record->rider && $record->rider->user) {
                            $record->rider->user->notify(new DeliveryStatusUpdatedNotification(
                                $record,
                                'assigned',
                                'picked_up'
                            ));
                        }

                        Notification::make()
                            ->success()
                            ->title('Pickup Confirmed')
                            ->body('Delivery marked as picked up.')
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('mark_in_transit')
                ->label('Mark In Transit')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('primary')
                ->visible(fn ($record) => $record->status === 'picked_up')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->update(['status' => 'in_transit']);

                    if ($record->rider && $record->rider->user) {
                        $record->rider->user->notify(new DeliveryStatusUpdatedNotification(
                            $record,
                            'picked_up',
                            'in_transit'
                        ));
                    }

                    Notification::make()
                        ->success()
                        ->title('Status Updated')
                        ->body('Delivery marked as in transit.')
                        ->send();
                }),

            Action::make('mark_delivered')
                ->label('Mark Delivered')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => in_array($record->status, ['picked_up', 'in_transit']))
                ->requiresConfirmation()
                ->modalHeading('Confirm Delivery')
                ->modalDescription('This will mark the delivery as completed and process all payments and commissions for all orders.')
                ->form([
                    FileUpload::make('proof_of_delivery')
                        ->label('Proof of Delivery (Image)')
                        ->image()
                        ->maxSize(5120)
                        ->helperText('Optional: Upload delivery confirmation photo'),
                ])
                ->action(function ($record, array $data) {
                    try {
                        $fulfillmentService = app(OrderFulfillmentService::class);
                        $results = $fulfillmentService->handleDeliveryCompletion($record, $data);

                        if ($record->rider && $record->rider->user) {
                            $record->rider->user->notify(new DeliveryStatusUpdatedNotification(
                                $record,
                                $record->getOriginal('status'),
                                'delivered'
                            ));
                        }

                        // Notify physician for all commissions created
                        if ($results['orders_processed'] > 0) {
                            $physician = $record->prescription->physician;

                            if ($physician && $physician->user) {
                                // Get all commissions for this delivery
                                $commissions = $record->prescription->commissions()
                                    ->whereIn('order_id', $record->orders->pluck('id'))
                                    ->get();

                                foreach ($commissions as $commission) {
                                    $physician->user->notify(new CommissionEarnedNotification($commission));
                                }
                            }
                        }

                        $message = "Delivery completed! {$results['orders_processed']} order(s) processed.";

                        if (! empty($results['errors'])) {
                            $message .= ' However, some errors occurred: '.implode(', ', $results['errors']);
                        }

                        Notification::make()
                            ->success()
                            ->title('Delivery Completed')
                            ->body($message)
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body('Failed to complete delivery: '.$e->getMessage())
                            ->send();
                    }
                }),

            Action::make('mark_failed')
                ->label('Mark Failed')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn ($record) => in_array($record->status, ['assigned', 'picked_up', 'in_transit']))
                ->requiresConfirmation()
                ->form([
                    Textarea::make('failure_reason')
                        ->label('Reason for Failure')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function ($record, array $data) {
                    try {
                        $fulfillmentService = app(OrderFulfillmentService::class);
                        $fulfillmentService->handleDeliveryFailure($record, $data['failure_reason']);

                        if ($record->rider && $record->rider->user) {
                            $record->rider->user->notify(new DeliveryStatusUpdatedNotification(
                                $record,
                                $record->getOriginal('status'),
                                'failed'
                            ));
                        }

                        Notification::make()
                            ->warning()
                            ->title('Delivery Failed')
                            ->body('Delivery marked as failed. Rider has been freed up.')
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
