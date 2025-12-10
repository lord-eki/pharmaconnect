<?php

namespace App\Filament\Supplier\Resources\Supplier\Orders\Pages;

use App\Filament\Supplier\Resources\Supplier\Orders\OrderResource;
use App\Services\OrderFulfillmentService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirm')
                ->label('Confirm Order')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => $record->status === 'pending')
                ->requiresConfirmation()
                ->modalHeading('Confirm Order')
                ->modalDescription('Confirm that you can fulfill this order. Operations will then process and assign a rider.')
                ->form([
                    DateTimePicker::make('expected_delivery')
                        ->label('Expected Delivery Date')
                        ->required()
                        ->minDate(now())
                        ->default(now()->addDays(2)),
                ])
                ->action(function ($record, array $data) {
                    try {
                        $fulfillmentService = app(OrderFulfillmentService::class);

                        // Only confirm the order, don't assign rider yet
                        $record->update([
                            'status' => 'confirmed',
                            'expected_delivery' => $data['expected_delivery'],
                        ]);

                        // Update stock quantities
                        foreach ($record->items as $item) {
                            $supplierMedicine = $item->medicine->supplierMedicines()
                                ->where('supplier_id', $record->supplier_id)
                                ->first();

                            if ($supplierMedicine) {
                                $supplierMedicine->decrement('stock_quantity', $item->quantity);
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title('Order Confirmed')
                            ->body('Order confirmed successfully. Operations team will process the delivery.')
                            ->send();

                        // TODO: Notify operations team about confirmed order

                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body('Failed to confirm order: '.$e->getMessage())
                            ->send();
                    }
                }),

            Action::make('mark_ready')
                ->label('Mark Ready for Pickup')
                ->icon('heroicon-o-check-badge')
                ->color('primary')
                ->visible(fn ($record) => $record->status === 'processing' && $record->delivery)
                ->requiresConfirmation()
                ->modalHeading('Mark Order as Ready for Pickup')
                ->modalDescription('The order is packed and ready for the rider to pick up.')
                ->form([
                    TextInput::make('tracking_number')
                        ->label('Tracking Number (Optional)')
                        ->maxLength(255),
                    Textarea::make('shipping_notes')
                        ->label('Notes for Rider')
                        ->maxLength(500)
                        ->placeholder('Any special handling instructions...'),
                ])
                ->action(function ($record, array $data) {
                    $notes = $record->notes ?? '';
                    $notes .= "\n\nReady for pickup: ".now()->toDateTimeString();

                    if (! empty($data['tracking_number'])) {
                        $notes .= "\nTracking: ".$data['tracking_number'];
                    }

                    if (! empty($data['shipping_notes'])) {
                        $notes .= "\nNotes: ".$data['shipping_notes'];
                    }

                    $record->update([
                        'notes' => $notes,
                    ]);

                    // Update delivery status
                    if ($record->delivery) {
                        $record->delivery->update([
                            'status' => 'ready_for_pickup',
                            'delivery_notes' => $data['shipping_notes'] ?? null,
                        ]);

                        // Notify rider
                        if ($record->delivery->rider) {
                            $record->delivery->rider->user->notify(
                                new \App\Notifications\DeliveryStatusUpdatedNotification(
                                    $record->delivery,
                                    'assigned',
                                    'ready_for_pickup'
                                )
                            );
                        }
                    }

                    Notification::make()
                        ->success()
                        ->title('Order Ready')
                        ->body('Order marked as ready for pickup. Rider has been notified.')
                        ->send();
                }),

            Action::make('cancel')
                ->label('Cancel Order')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn ($record) => ! in_array($record->status, ['delivered', 'cancelled']))
                ->requiresConfirmation()
                ->modalHeading('Cancel Order')
                ->modalDescription('Are you sure you want to cancel this order? Stock will be restored.')
                ->form([
                    Textarea::make('cancellation_reason')
                        ->label('Reason for Cancellation')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function ($record, array $data) {
                    // Restore stock if order was confirmed
                    if (in_array($record->status, ['confirmed', 'processing', 'shipped'])) {
                        foreach ($record->items as $item) {
                            $supplierMedicine = \App\Models\SupplierMedicine::where('supplier_id', $record->supplier_id)
                                ->where('medicine_id', $item->medicine_id)
                                ->first();

                            if ($supplierMedicine) {
                                $supplierMedicine->increment('stock_quantity', $item->quantity);
                                $supplierMedicine->update(['last_updated' => now()]);
                            }
                        }
                    }

                    $record->update([
                        'status' => 'cancelled',
                        'notes' => ($record->notes ?? '')."\n\nCancelled: ".now()->toDateTimeString()."\nReason: ".$data['cancellation_reason'],
                    ]);

                    // Cancel delivery if exists
                    if ($record->delivery) {
                        $record->delivery->update(['status' => 'cancelled']);

                        // Free up rider
                        if ($record->delivery->rider) {
                            $record->delivery->rider->update(['is_available' => true]);
                        }
                    }

                    Notification::make()
                        ->success()
                        ->title('Order Cancelled')
                        ->body('Order cancelled and stock restored.')
                        ->send();
                }),

            Action::make('print')
                ->label('Print Order')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->openUrlInNewTab(),
        ];
    }
}