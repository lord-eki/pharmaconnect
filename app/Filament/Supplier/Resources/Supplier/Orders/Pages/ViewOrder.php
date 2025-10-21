<?php

namespace App\Filament\Supplier\Resources\Supplier\Orders\Pages;

use App\Filament\Supplier\Resources\Supplier\Orders\OrderResource;
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
                ->modalDescription('Confirm that you can fulfill this order. Stock will be deducted.')
                ->form([
                    DateTimePicker::make('expected_delivery')
                        ->label('Expected Delivery Date')
                        ->required()
                        ->minDate(now())
                        ->default(now()->addDays(2)),
                ])
                ->action(function ($record, array $data) {
                    $record->update([
                        'status' => 'confirmed',
                        'expected_delivery' => $data['expected_delivery'],
                    ]);

                    // Deduct stock
                    foreach ($record->orderItems as $item) {
                        $supplierMedicine = \App\Models\SupplierMedicine::where('supplier_id', $record->supplier_id)
                            ->where('medicine_id', $item->medicine_id)
                            ->first();

                        if ($supplierMedicine) {
                            $supplierMedicine->decrement('stock_quantity', $item->quantity);
                            $supplierMedicine->update(['last_updated' => now()]);
                        }
                    }

                    Notification::make()
                        ->success()
                        ->title('Order confirmed successfully')
                        ->body('Stock has been deducted from your inventory.')
                        ->send();
                }),
                // ->after(fn () => redirect()->route('filament.supplier.resources.orders.view', ['record' => $this->getRecord()])),

            Action::make('mark_processing')
                ->label('Start Processing')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->visible(fn ($record) => $record->status === 'confirmed')
                ->requiresConfirmation()
                ->action(fn ($record) => $record->update(['status' => 'processing']))
                ->successNotificationTitle('Order marked as processing'),
                // ->after(fn () => redirect()->route('filament.supplier.resources.orders.view', ['record' => $this->getRecord()])),

            Action::make('mark_shipped')
                ->label('Mark as Shipped')
                ->icon('heroicon-o-truck')
                ->color('primary')
                ->visible(fn ($record) => in_array($record->status, ['confirmed', 'processing']))
                ->requiresConfirmation()
                ->modalHeading('Mark Order as Shipped')
                ->form([
                    TextInput::make('tracking_number')
                        ->label('Tracking Number (Optional)')
                        ->maxLength(255),
                    Textarea::make('shipping_notes')
                        ->label('Shipping Notes')
                        ->maxLength(500),
                ])
                ->action(function ($record, array $data) {
                    $notes = $record->notes ?? '';
                    $notes .= "\n\nShipped: " . now()->toDateTimeString();
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
                })
                ->successNotificationTitle('Order marked as shipped'),
                // ->after(fn () => redirect()->route('filament.supplier.resources.orders.view', ['record' => $this->getRecord()])),

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
                        foreach ($record->orderItems as $item) {
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
                })
                ->successNotificationTitle('Order cancelled and stock restored'),
                // ->after(fn () => redirect()->route('filament.supplier.resources.orders.index')),

            Action::make('print')
                ->label('Print Order')
                ->icon('heroicon-o-printer')
                ->color('gray')
                // ->url(fn ($record) => route(name: 'orders.print', $record))
                ->openUrlInNewTab(),
        ];
    }
}
