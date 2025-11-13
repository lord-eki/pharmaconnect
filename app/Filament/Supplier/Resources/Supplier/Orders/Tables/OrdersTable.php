<?php

namespace App\Filament\Supplier\Resources\Supplier\Orders\Tables;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order # (LPO)')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('prescription.prescription_number')
                    ->label('Prescription #')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('prescription.patient.first_name')
                    ->label('Patient')
                    ->formatStateUsing(fn ($record) => $record->prescription->patient
                            ? "{$record->prescription->patient->first_name} {$record->prescription->patient->last_name}"
                            : 'N/A'
                    )
                    ->searchable(['first_name', 'last_name'])
                    ->wrap(),

                TextColumn::make('supplier_total')
                    ->label('Total')
                    ->money('KES')
                    ->sortable()
                    ->summarize([
                        Sum::make()
                            ->money('KES')
                            ->label('Total Revenue'),
                    ]),

                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'processing' => 'primary',
                        'shipped' => 'success',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('ordered_at')
                    ->label('Order Date')
                    ->dateTime()
                    ->sortable()
                    ->since(),

                TextColumn::make('expected_delivery')
                    ->label('Expected Delivery')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($state, $record) => $state && $state->isPast() && $record->status !== 'delivered'
                            ? 'danger'
                            : 'success'
                    )
                    ->description(fn ($state) => $state ? $state->diffForHumans() : null),

                IconColumn::make('is_overdue')
                    ->label('Overdue')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),

                Filter::make('overdue')
                    ->label('Overdue Orders')
                    ->query(fn (Builder $query) => $query
                        ->where('expected_delivery', '<', now())
                        ->whereNotIn('status', ['delivered', 'cancelled'])
                    ),

                Filter::make('ordered_at')
                    ->form([
                        DatePicker::make('ordered_from')
                            ->label('Ordered From'),
                        DatePicker::make('ordered_until')
                            ->label('Ordered Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['ordered_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('ordered_at', '>=', $date),
                            )
                            ->when(
                                $data['ordered_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('ordered_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),

                    EditAction::make()
                        ->visible(fn ($record) => ! in_array($record->status, ['delivered', 'cancelled'])),

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
                        ->action(function (Order $record, array $data) {
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

                    Action::make('mark_processing')
                        ->label('Start Processing')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->visible(fn ($record) => $record->status === 'confirmed')
                        ->requiresConfirmation()
                        ->action(fn (Order $record) => $record->update(['status' => 'processing']))
                        ->successNotificationTitle('Order marked as processing'),

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
                        ->action(function (Order $record, array $data) {
                            $notes = $record->notes ?? '';
                            $notes .= "\n\nShipped: ".now()->toDateTimeString();
                            if (! empty($data['tracking_number'])) {
                                $notes .= "\nTracking: ".$data['tracking_number'];
                            }
                            if (! empty($data['shipping_notes'])) {
                                $notes .= "\nNotes: ".$data['shipping_notes'];
                            }

                            $record->update([
                                'status' => 'shipped',
                                'notes' => $notes,
                            ]);
                        })
                        ->successNotificationTitle('Order marked as shipped'),

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
                        ->action(function (Order $record, array $data) {
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
                                'notes' => ($record->notes ?? '')."\n\nCancelled: ".now()->toDateTimeString()."\nReason: ".$data['cancellation_reason'],
                            ]);
                        })
                        ->successNotificationTitle('Order cancelled and stock restored'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_mark_processing')
                        ->label('Mark as Processing')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->status === 'confirmed') {
                                    $record->update(['status' => 'processing']);
                                }
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Selected orders marked as processing'),
                ]),
            ])->defaultSort('ordered_at', 'desc')
            ->poll('60s');
    }
}
