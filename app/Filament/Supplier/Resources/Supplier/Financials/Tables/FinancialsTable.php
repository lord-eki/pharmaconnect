<?php

namespace App\Filament\Supplier\Resources\Supplier\Financials\Tables;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\Delivery;


class FinancialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_reference')
                    ->label('Payment Ref')
                    ->searchable()
                    ->sortable()
                    ->copyable()
       ,

                TextColumn::make('order.order_number')
                    ->label('Order / Delivery')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        if (! $record->order) {
                            return 'N/A';
                        }

                        $orderId = $record->order->id;

                        // First check: Is this order in the delivery_order pivot table?
                        $deliveryRecord = DB::table('delivery_order')
                            ->where('order_id', $orderId)
                            ->first();

                        if ($deliveryRecord) {
                            $delivery = Delivery::find($deliveryRecord->delivery_id);

                            if ($delivery) {
                                // Count orders in this delivery
                                $orderCount = DB::table('delivery_order')
                                    ->where('delivery_id', $delivery->id)
                                    ->count();

                                // Show delivery number if multiple orders
                                if ($orderCount > 1) {
                                    return $delivery->delivery_number;
                                }
                            }
                        }

                        // Second check: Is there a delivery with this order_id as primary?
                        $delivery = Delivery::where('order_id', $orderId)->first();

                        if ($delivery) {
                            // Count orders linked via pivot
                            $orderCount = DB::table('delivery_order')
                                ->where('delivery_id', $delivery->id)
                                ->count();

                            if ($orderCount > 1) {
                                return $delivery->delivery_number;
                            }
                        }

                        return $record->order->order_number;
                    })
                    ->description(function ($record) {
                        if (! $record->order) {
                            return null;
                        }

                        $orderId = $record->order->id;
                        $delivery = null;
                        $orderCount = 0;

                        // Check pivot table
                        $deliveryRecord = DB::table('delivery_order')
                            ->where('order_id', $orderId)
                            ->first();

                        if ($deliveryRecord) {
                            $delivery = Delivery::find($deliveryRecord->delivery_id);
                            if ($delivery) {
                                $orderCount = DB::table('delivery_order')
                                    ->where('delivery_id', $delivery->id)
                                    ->count();
                            }
                        } else {
                            // Check direct relationship
                            $delivery = Delivery::where('order_id', $orderId)->first();
                            if ($delivery) {
                                $orderCount = DB::table('delivery_order')
                                    ->where('delivery_id', $delivery->id)
                                    ->count();
                            }
                        }

                        if ($delivery && $orderCount > 1) {
                            return "Your Order: {$record->order->order_number} ({$orderCount} total)";
                        }

                        return null;
                    })
                    ->badge()
                    ->color(function ($record) {
                        if (! $record->order) {
                            return 'gray';
                        }

                        $orderId = $record->order->id;

                        // Check pivot table
                        $deliveryRecord = DB::table('delivery_order')
                            ->where('order_id', $orderId)
                            ->first();

                        if ($deliveryRecord) {
                            $orderCount = DB::table('delivery_order')
                                ->where('delivery_id', $deliveryRecord->delivery_id)
                                ->count();

                            if ($orderCount > 1) {
                                return 'warning';
                            }
                        } else {
                            // Check direct relationship
                            $delivery = Delivery::where('order_id', $orderId)->first();
                            if ($delivery) {
                                $orderCount = DB::table('delivery_order')
                                    ->where('delivery_id', $delivery->id)
                                    ->count();

                                if ($orderCount > 1) {
                                    return 'warning';
                                }
                            }
                        }

                        return 'info';
                    })
                    ->tooltip(function ($record) {
                        if (! $record->order) {
                            return null;
                        }

                        $orderId = $record->order->id;
                        $delivery = null;

                        // Check pivot table
                        $deliveryRecord = DB::table('delivery_order')
                            ->where('order_id', $orderId)
                            ->first();

                        if ($deliveryRecord) {
                            $delivery = Delivery::find($deliveryRecord->delivery_id);
                        } else {
                            // Check direct relationship
                            $delivery = Delivery::where('order_id', $orderId)->first();
                        }

                        if ($delivery) {
                            $orderCount = DB::table('delivery_order')
                                ->where('delivery_id', $delivery->id)
                                ->count();

                            if ($orderCount > 1) {
                                $orderNumbers = DB::table('delivery_order')
                                    ->join('orders', 'delivery_order.order_id', '=', 'orders.id')
                                    ->where('delivery_order.delivery_id', $delivery->id)
                                    ->pluck('orders.order_number')
                                    ->join("\n");

                                return "All orders in this delivery:\n".$orderNumbers;
                            }
                        }

                        return null;
                    }),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable()
                    ->summarize([
                        Sum::make()
                            ->money('KES')
                            ->label('Total Received'),
                    ]),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'mpesa' => 'M-Pesa',
                        'card' => 'Card',
                        'bank_transfer' => 'Bank',
                        'cash' => 'Cash',
                        'insurance' => 'Insurance',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Payment Date')
                    ->dateTime()
                    ->sortable()
                    ->since(),

            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ])
                    ->multiple(),

                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'mpesa' => 'M-Pesa',
                        'card' => 'Card Payment',
                        'bank_transfer' => 'Bank Transfer',
                        'cash' => 'Cash',
                        'insurance' => 'Insurance',
                    ])
                    ->multiple(),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Payment From'),
                        DatePicker::make('created_until')
                            ->label('Payment Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
            ])
            ->toolbarActions([

            ])->defaultSort('created_at', 'desc')
            ->poll('60s');
    }
}
