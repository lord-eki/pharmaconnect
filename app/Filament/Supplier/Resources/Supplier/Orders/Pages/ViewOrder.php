<?php

namespace App\Filament\Supplier\Resources\Supplier\Orders\Pages;

use App\Filament\Supplier\Resources\Supplier\Orders\OrderResource;
use App\Services\OrderFulfillmentService;
use App\Services\OrderReassignmentService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

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
                ->visible(fn ($record) => $record->status === 'pending' || $record->status === 'sent_to_supplier')
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

                        $record->update([
                            'status' => 'confirmed',
                            'expected_delivery' => $data['expected_delivery'],
                        ]);

                        $record->load('items.medicine.supplierMedicines');

                        foreach ($record->items as $item) {
                            $supplierMedicine = $item->medicine->supplierMedicines
                                ->where('supplier_id', $record->supplier_id)
                                ->first();

                            if ($supplierMedicine) {
                                $supplierMedicine->decrement('stock_quantity', $item->quantity);
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title('Order Confirmed')
                            ->body('Order confirmed successfully.')
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body('Failed to confirm order: '.$e->getMessage())
                            ->send();
                    }
                }),

            Action::make('reject')
                ->label('Reject Order')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn ($record) => in_array($record->status, ['pending', 'sent_to_supplier']))
                ->requiresConfirmation()
                ->modalHeading('Reject Order')
                ->modalDescription('Reject this order.')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Reason for Rejection')
                        ->required()
                        ->placeholder('e.g., Out of stock, Cannot deliver to location, etc.')
                        ->maxLength(1000)
                        ->rows(3),
                ])
                ->action(function ($record, array $data) {
                    try {
                        $reassignmentService = app(OrderReassignmentService::class);

                        $reassignmentService->rejectOrder(
                            $record,
                            $data['rejection_reason'],
                            auth()->id()
                        );

                        Notification::make()->success()->title('Order Cancellation')->body('Order has been cancelled successfully')->send();

                        // $autoReassigned = $reassignmentService->autoReassignToNextSupplier($record);

                        // if ($autoReassigned) {
                        //     Notification::make()
                        //         ->success()
                        //         ->title('Order Rejected & Reassigned')
                        //         ->body('Order has been automatically reassigned to the next available supplier.')
                        //         ->send();
                        // } else {
                        //     Notification::make()
                        //         ->warning()
                        //         ->title('Order Rejected')
                        //         ->body('Order rejected. Operations team will manually reassign this order.')
                        //         ->send();
                        // }



                    } catch (\Exception $e) {
                        \Log::error('Order rejection failed', [
                            'order_id' => $record->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);

                        Notification::make()
                            ->danger()
                            ->title('Rejection Failed')
                            ->body('Failed to reject order: '.$e->getMessage())
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

                    $record->update(['notes' => $notes]);

                    if ($record->delivery) {
                        $record->delivery->update([
                            'status' => 'ready_for_pickup',
                            'delivery_notes' => $data['shipping_notes'] ?? null,
                        ]);

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

            Action::make('print')
                ->label('View PDF')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn ($record) => route('supplier.orders.print', $record))
                ->openUrlInNewTab(),

            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn ($record) => route('supplier.orders.download', $record))
                ->openUrlInNewTab(),
            
        ];
    }

    public function getRecord(): Model
    {
        return parent::getRecord()->loadMissing([
            'items.medicine',
        ]);
    }
}