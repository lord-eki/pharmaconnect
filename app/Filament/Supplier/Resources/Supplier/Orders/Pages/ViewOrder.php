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
            EditAction::make()
                ->visible(fn ($record) => !in_array($record->status, ['delivered', 'cancelled'])),

            Action::make('confirm')
                ->label('Confirm Order')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => $record->status === 'pending')
                ->requiresConfirmation()
                ->modalHeading('Confirm Order')
                ->modalDescription('Confirm that you can fulfill this order. A delivery will be created and rider assigned automatically.')
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
                        
                        $results = $fulfillmentService->handleOrderConfirmation($record, $data);

                        $message = 'Order confirmed successfully!';
                        
                        if ($results['rider_assigned']) {
                            $rider = $results['rider'];
                            $message .= " Rider {$rider['name']} ({$rider['vehicle']}) has been assigned.";
                        } else {
                            $message .= ' However, no rider was automatically assigned. Please assign one manually in the Operations panel.';
                        }

                        Notification::make()
                            ->success()
                            ->title('Order Confirmed')
                            ->body($message)
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body('Failed to confirm order: ' . $e->getMessage())
                            ->send();
                    }
                }),

            Action::make('mark_processing')
                ->label('Start Processing')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->visible(fn ($record) => $record->status === 'confirmed')
                ->requiresConfirmation()
                ->modalHeading('Start Processing Order')
                ->modalDescription('Mark this order as being processed. If not already done, a delivery will be created.')
                ->action(function ($record) {
                    try {
                        $fulfillmentService = app(OrderFulfillmentService::class);
                        $fulfillmentService->handleOrderProcessing($record);

                        Notification::make()
                            ->success()
                            ->title('Order Processing')
                            ->body('Order marked as processing.')
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body('Failed to process order: ' . $e->getMessage())
                            ->send();
                    }
                }),

            Action::make('mark_shipped')
                ->label('Mark as Shipped/Ready for Pickup')
                ->icon('heroicon-o-truck')
                ->color('primary')
                ->visible(fn ($record) => in_array($record->delivery->status, ['assigned']))
                ->requiresConfirmation()
                ->modalHeading('Mark Order as Ready for Pickup')
                ->modalDescription('The order is ready for the rider to pick up.')
                ->form([
                    TextInput::make('tracking_number')
                        ->label('Tracking Number (Optional)')
                        ->maxLength(255),
                    Textarea::make('shipping_notes')
                        ->label('Notes for Rider')
                        ->maxLength(500),
                ])
                ->action(function ($record, array $data) {
                    $notes = $record->notes ?? '';
                    $notes .= "\n\nReady for pickup: " . now()->toDateTimeString();
                    
                    if (!empty($data['tracking_number'])) {
                        $notes .= "\nTracking: " . $data['tracking_number'];
                    }
                    
                    if (!empty($data['shipping_notes'])) {
                        $notes .= "\nNotes: " . $data['shipping_notes'];
                    }

                    $record->update([
                        'status' => 'shipped',
                        'notes' => $notes,
                    ]);

                    // Update delivery status if exists
                    if ($record->delivery) {
                        $record->delivery->update(['status' => 'picked_up']);
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
                ->visible(fn ($record) => !in_array($record->status, ['delivered', 'cancelled']))
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
                        'notes' => ($record->notes ?? '') . "\n\nCancelled: " . now()->toDateTimeString() . "\nReason: " . $data['cancellation_reason'],
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

            Action::make('view_delivery')
                ->label('View Delivery')
                ->icon('heroicon-o-map-pin')
                ->color('info')
                ->visible(fn ($record) => $record->delivery !== null),
                // ->url(fn ($record) => route('filament.operations.resources.deliveries.view', ['record' => $record->delivery]))
                // ->openUrlInNewTab(),

            Action::make('print')
                ->label('Print Order')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->openUrlInNewTab(),
        ];
    }
}