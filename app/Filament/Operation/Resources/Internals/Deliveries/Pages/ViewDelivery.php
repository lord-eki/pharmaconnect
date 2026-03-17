<?php

namespace App\Filament\Operation\Resources\Internals\Deliveries\Pages;

use App\Filament\Operation\Resources\Internals\Deliveries\DeliveryResource;
use App\Models\Rider;
use App\Notifications\CommissionEarnedNotification;
use App\Notifications\DeliveryAssignedNotification;
use App\Notifications\DeliveryStatusUpdatedNotification;
use App\Notifications\RiderReassignedNotification;
use App\Services\OrderFulfillmentService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;

class ViewDelivery extends ViewRecord
{
    protected static string $resource = DeliveryResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make()
                    ->schema([
                        Grid::make(5)
                            ->schema([
                                TextEntry::make('delivery_number')
                                    ->label('Delivery #')
                                    ->weight('bold'),

                                TextEntry::make('status')
                                    ->label('Status')
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
                                    ->label('Orders')
                                    ->state(fn ($record) => $record->orders->count())
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('delivery_fee')
                                    ->label('Delivery Fee')
                                    ->money('KES')
                                    ->weight('bold'),

                                TextEntry::make('total_amount')
                                    ->label('Orders Total')
                                    ->state(fn ($record) => $record->orders->sum('total_amount'))
                                    ->money('KES')
                                    ->weight('bold'),
                            ]),
                    ])->columnSpanFull()
                    ->compact(),


                    Grid::make(4)
                        ->schema([

                            Tabs::make()
                                ->columnSpan(3)
                                ->tabs([

                                    Tab::make('Delivery')
                                        ->icon('heroicon-o-map-pin')
                                        ->schema([

                                            Section::make('Pickup')
                                                ->schema([
                                                    Grid::make(3)
                                                        ->schema([
                                                            TextEntry::make('pickup_address')
                                                                ->label('Address'),

                                                            TextEntry::make('scheduled_pickup')
                                                                ->label('Scheduled')
                                                                ->dateTime('M d, Y H:i'),

                                                            TextEntry::make('actual_pickup')
                                                                ->label('Actual')
                                                                ->dateTime('M d, Y H:i')
                                                                ->placeholder('—'),
                                                        ]),
                                                ])
                                                ->compact(),

                                            Section::make('Destination')
                                                ->schema([
                                                    Grid::make(4)
                                                        ->schema([
                                                            TextEntry::make('delivery_address')
                                                                ->label('Address'),

                                                            TextEntry::make('recipient_name')
                                                                ->label('Recipient'),

                                                            TextEntry::make('recipient_phone')
                                                                ->label('Phone'),


                                                            TextEntry::make('estimated_delivery')
                                                                ->label('Est. Delivery')
                                                                ->dateTime('M d, Y H:i'),

                                                            TextEntry::make('actual_delivery')
                                                                ->label('Actual Delivery')
                                                                ->dateTime('M d, Y H:i')
                                                                ->placeholder('—'),

                                                            TextEntry::make('delivery_notes')
                                                                ->label('Notes')
                                                                ->placeholder('No notes'),
                                                        ]),
                                                ])
                                                ->compact(),
                                        ]),

                                    Tab::make('Orders')
                                        ->icon('heroicon-o-shopping-bag')
                                        ->schema([
                                            RepeatableEntry::make('orders')
                                                ->label('')
                                                ->schema([
                                                    Grid::make(4)
                                                        ->schema([
                                                            TextEntry::make('order_number')
                                                                ->label('Order #')
                                                                ->weight('bold'),

                                                            TextEntry::make('supplier.company_name')
                                                                ->label('Supplier'),

                                                            TextEntry::make('status')
                                                                ->label('Status')
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
                                                                ->label('Amount')
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
                                                                ->placeholder('—'),
                                                        ]),
                                                ])
                                                ->columns(1),
                                        ]),

                                    Tab::make('Prescription')
                                        ->icon('heroicon-o-document-text')
                                        ->visible(fn ($record) => $record->prescription_id !== null)
                                        ->schema([
                                            Grid::make(2)
                                                ->schema([
                                                    TextEntry::make('prescription.prescription_number')
                                                        ->label('Prescription #')
                                                        ->color(Color::Blue)
                                                        ->weight('bold')
                                                        ->placeholder('—'),

                                                    TextEntry::make('prescription.physician.name')
                                                        ->label('Physician')
                                                        ->placeholder('—'),

                                                    TextEntry::make('prescription.patient.full_name')
                                                        ->label('Patient')
                                                        ->placeholder('—'),

                                                    TextEntry::make('total_amount')
                                                        ->label('Orders Total')
                                                        ->state(fn ($record) => $record->orders->sum('total_amount'))
                                                        ->money('KES')
                                                        ->weight('bold'),
                                                ]),
                                        ]),

                                    Tab::make('Insurer Order')
                                        ->icon('heroicon-o-building-office')
                                        ->visible(fn ($record) => $record->prescription_id === null && $record->external_order_id !== null)
                                        ->schema([
                                            Grid::make(2)
                                                ->schema([
                                                    TextEntry::make('externalOrder.order_number')
                                                        ->label('External Order #')
                                                        ->color(Color::Blue)
                                                        ->weight('bold'),

                                                    TextEntry::make('externalOrder.insuranceProvider.company_name')
                                                        ->label('Insurance Provider'),

                                                    TextEntry::make('recipient_name')
                                                        ->label('Recipient'),

                                                    TextEntry::make('total_amount')
                                                        ->label('Orders Total')
                                                        ->state(fn ($record) => $record->orders->sum('total_amount'))
                                                        ->money('KES')
                                                        ->weight('bold'),
                                                ]),
                                        ]),
                                ]),

                            Section::make('Rider')
                                ->icon('heroicon-o-user-circle')
                                ->columnSpan(1)
                                ->schema([
                                    TextEntry::make('rider.full_name')
                                        ->label('Name')
                                        ->default('Not Assigned')
                                        ->badge()
                                        ->color(fn ($state) => $state === 'Not Assigned' ? 'danger' : 'success'),

                                    TextEntry::make('rider.phone')
                                        ->label('Phone')
                                        ->default('—'),

                                    TextEntry::make('rider.vehicle_type')
                                        ->label('Vehicle')
                                        ->default('—'),

                                    TextEntry::make('rider.vehicle_registration')
                                        ->label('Plate')
                                        ->default('—'),

                                    TextEntry::make('rider.rating')
                                        ->label('Rating')
                                        ->default('—')
                                        ->suffix(' / 5'),
                                ])
                                ->visible(fn ($record) => $record->rider_id !== null),

                        
                ])->columnSpanFull(),

            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
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
                ])
                ->action(function ($record, array $data) {
                    try {
                        $rider = Rider::findOrFail($data['rider_id']);

                        $record->update([
                            'rider_id' => $data['rider_id'],
                            'status' => 'assigned',
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
                                $record, 'assigned', 'picked_up'
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
                            $record, 'picked_up', 'in_transit'
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
                                $record, $record->getOriginal('status'), 'delivered'
                            ));
                        }

                        if ($results['orders_processed'] > 0 && $record->prescription_id && $record->prescription) {
                            $physician = $record->prescription->physician;

                            if ($physician && $physician->user) {
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
                                $record, $record->getOriginal('status'), 'failed'
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
