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
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Log;

class ViewDelivery extends ViewRecord
{
    protected static string $resource = DeliveryResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Delivery Overview')
                    ->schema([
                        Grid::make(3)
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

                                TextEntry::make('delivery_fee')
                                    ->money('KES'),
                            ]),
                    ]),

                Section::make('Order Details')
                    ->schema([
                        TextEntry::make('order.order_number')
                            ->label('Order Number')
                            // ->url(fn ($record): string => route('filament.operations.resources.orders.view', ['record' => $record->order_id]))
                            ->color(Color::Blue),

                        TextEntry::make('order.status')
                            ->label('Order Status')
                            ->badge(),

                        TextEntry::make('order.total_amount')
                            ->label('Order Amount')
                            ->money('KES'),

                        TextEntry::make('order.prescription.physician.full_name')
                            ->label('Physician'),

                        TextEntry::make('order.prescription.patient.full_name')
                            ->label('Patient'),
                    ])
                    ->columns(3),

                Section::make('Rider Information')
                    ->schema([
                        TextEntry::make('rider.last_name')
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
                            ->default(null),
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
                            ->default(null),

                        TextEntry::make('estimated_distance_km')
                            ->label('Distance')
                            ->suffix(' km'),

                        TextEntry::make('delivery_notes')
                            ->columnSpanFull()
                            ->default('No notes'),
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
            // STEP 1: Start Processing (Only for confirmed orders without delivery started)
            Action::make('start_processing')
                ->label('Start Processing')
                ->icon('heroicon-o-play')
                ->color('info')
                ->visible(fn ($record) => $record->order->status === 'confirmed' && $record->status === 'pending')
                ->requiresConfirmation()
                ->modalHeading('Start Processing Order')
                ->modalDescription('This will mark the order as processing. You can then assign a rider.')
                ->action(function ($record) {
                    try {
                        // Update order status to processing
                        $record->order->update(['status' => 'processing']);

                        Notification::make()
                            ->success()
                            ->title('Processing Started')
                            ->body('Order is now in processing. You can now assign a rider.')
                            ->send();

                        // Refresh the page to show new actions
                        $this->redirect(static::getUrl(['record' => $record->id]));

                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body('Failed to start processing: '.$e->getMessage())
                            ->send();
                    }
                }),

            // STEP 2: Assign Rider (Only after processing started)
            Action::make('assign_rider')
                ->label('Assign Rider')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->visible(fn ($record) => $record->order->status === 'processing' && $record->status === 'pending' && ! $record->rider_id)
                ->form([
                    Select::make('rider_id')
                        ->label('Select Rider')
                        ->options(
                            Rider::active()
                                ->available()
                                ->pluck('last_name', 'id')
                        )
                        ->required()
                        ->searchable()
                        ->helperText('Only available riders are shown'),
                ])
                ->action(function ($record, array $data) {
                    try {
                        $fulfillmentService = app(OrderFulfillmentService::class);
                        $rider = $fulfillmentService->reassignRider($record, $data['rider_id']);

                        if ($rider) {
                            $rider->user->notify(new DeliveryAssignedNotification($record));
                            Notification::make()
                                ->success()
                                ->title('Rider Assigned')
                                ->body("Rider {$rider->last_name} has been assigned to this delivery. Email notification sent.")
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            // STEP 3: Reassign Rider (If needed)
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
                                ->pluck('last_name', 'id')
                        )
                        ->required()
                        ->searchable(),

                    Textarea::make('reason')
                        ->label('Reason for Reassignment')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function ($record, array $data) {
                    try {
                        $oldRider = $record->rider;

                        $fulfillmentService = app(OrderFulfillmentService::class);
                        $rider = $fulfillmentService->reassignRider($record, $data['rider_id']);

                        if ($oldRider) {
                            $oldRider->user->notify(new DeliveryStatusUpdatedNotification($record, 'assigned', 'reassigned'));
                        }

                        $rider->user->notify(new RiderReassignedNotification($record, $data['reason']));

                        // Log the reassignment
                        $record->update([
                            'delivery_notes' => ($record->delivery_notes ?? '').
                                "\n\nRider reassigned: ".now()->toDateTimeString().
                                "\nReason: ".$data['reason'],
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Rider Reassigned')
                            ->body("New rider {$rider->last_name} has been assigned. Email notifications sent.")
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
                ->visible(fn ($record) => in_array($record->status, ['assigned', 'ready_for_pickup']))
                ->requiresConfirmation()
                ->action(function ($record) {
                    try {
                        $fulfillmentService = app(OrderFulfillmentService::class);
                        $fulfillmentService->handlePickup($record);

                        // Send email notification to rider
                        if ($record->rider) {
                            $record->rider->user->notify(new DeliveryStatusUpdatedNotification(
                                $record,
                                $record->getOriginal('status'),
                                'picked_up'
                            ));
                        }

                        Notification::make()
                            ->success()
                            ->title('Pickup Confirmed')
                            ->body('Delivery marked as picked up. Rider notified via email.')
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
                    $oldStatus = $record->status;
                    $record->update(['status' => 'in_transit']);

                    // Send email notification to rider
                    if ($record->rider) {
                        $record->rider->user->notify(new DeliveryStatusUpdatedNotification(
                            $record,
                            $oldStatus,
                            'in_transit'
                        ));
                    }

                    Notification::make()
                        ->success()
                        ->title('Status Updated')
                        ->body('Delivery marked as in transit. Rider notified via email.')
                        ->send();
                }),

            Action::make('mark_delivered')
                ->label('Mark Delivered')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => in_array($record->status, ['picked_up', 'in_transit']))
                ->requiresConfirmation()
                ->modalHeading('Confirm Delivery')
                ->modalDescription('This will mark the delivery as completed and process all payments and commissions.')
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

                        if ($record->rider) {
                            $record->rider->user->notify(new DeliveryStatusUpdatedNotification(
                                $record,
                                $record->getOriginal('status'),
                                'delivered'
                            ));
                        }

                        // Notify physician if commission created
                        if ($results['commission_created']) {
                            $commission = $results['commission'];
                            $physician = $record->order->prescription->physician;

                            if ($physician && $physician->user) {
                                $physician->user->notify(new CommissionEarnedNotification($commission));
                            } else {
                                Log::warning('Cannot notify physician - no user relationship', [
                                    'physician_id' => $physician?->id,
                                    'commission_id' => $commission->id ?? null,
                                ]);
                            }

                        }

                        $message = 'Delivery completed successfully! Rider notified via email.';

                        if ($results['payments_processed']) {
                            $message .= ' Payments processed.';
                        }

                        if ($results['commission_created']) {
                            $commission = $results['commission'];
                            $message .= " Commission of KES {$commission['amount']} created for physician.";
                        }

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
                ->visible(fn ($record) => in_array($record->status, ['assigned', 'ready_for_pickup', 'picked_up', 'in_transit']))
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

                        // Notify rider via email
                        if ($record->rider) {
                            $record->rider->user->notify(new DeliveryStatusUpdatedNotification(
                                $record,
                                $record->getOriginal('status'),
                                'failed'
                            ));
                        }

                        Notification::make()
                            ->warning()
                            ->title('Delivery Failed')
                            ->body('Delivery marked as failed. Rider has been freed up and notified via email.')
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('view_tracking')
                ->label('View Live Tracking')
                ->icon('heroicon-o-map')
                ->color('info')
                ->visible(fn ($record) => in_array($record->status, ['picked_up', 'in_transit']))
                // ->url(fn ($record) => route('filament.operations.resources.deliveries.tracking', ['record' => $record]))
                ->openUrlInNewTab(),

            Action::make('view_order')
                ->label('View Order')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                // ->url(fn ($record) => route('filament.operations.resources.orders.view', ['record' => $record->order_id]))
                ->openUrlInNewTab(),
        ];
    }
}
