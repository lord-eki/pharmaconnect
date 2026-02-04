<?php

namespace App\Filament\Rider\Resources\Deliveries\Tables;

use App\Models\Delivery;
use App\Notifications\DeliveryAssignedNotification;
use App\Notifications\DeliveryStatusUpdatedNotification;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class DeliveriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('delivery_number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->label('Delivery #'),


                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'assigned',
                        'primary' => 'picked_up',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-user' => 'assigned',
                        'heroicon-o-truck' => 'picked_up',
                        'heroicon-o-check-circle' => 'delivered',
                        'heroicon-o-x-circle' => 'cancelled',
                    ]),

                TextColumn::make('recipient_name')
                    ->searchable()
                    ->label('Recipient'),

                TextColumn::make('recipient_phone')
                    ->searchable()
                    ->copyable()
                    ->label('Phone'),

                TextColumn::make('delivery_address')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->delivery_address)
                    ->label('Delivery Address'),

                TextColumn::make('estimated_distance_km')
                    ->numeric(2)
                    ->suffix(' km')
                    ->sortable()
                    ->label('Distance'),

                TextColumn::make('actual_delivery')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->label('Delivered At')
                    ->placeholder('Not yet delivered'),

                TextColumn::make('created_at')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'assigned' => 'Assigned',
                        'picked_up' => 'Picked Up',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([

                    Action::make('view_orders')
                        ->label('View Orders')
                        ->icon('heroicon-o-shopping-bag')
                        ->color('info')
                        ->modalHeading(fn ($record) => "Orders for Delivery {$record->delivery_number}")
                        ->modalDescription(fn ($record) => $record->prescription
                            ? "Prescription: {$record->prescription->prescription_number} | {$record->orders->count()} order(s)"
                            : 'No prescription linked')
                        ->modalContent(function ($record) {
                            $orders = $record->orders()->with(['supplier', 'items.medicine'])->get();

                            return new HtmlString(
                                view('filament.components.delivery-orders', [
                                    'delivery' => $record,
                                    'orders' => $orders,
                                ])->render()
                            );
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->slideOver()
                        ->modalWidth('7xl'),

                    Action::make('accept')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn (Delivery $record) => $record->status === 'pending')
                        ->requiresConfirmation()
                        ->modalHeading('Accept Delivery')
                        ->modalDescription('Are you sure you want to accept this delivery?')
                        ->action(function (Delivery $record) {
                            try {
                                $record->update([
                                    'status' => 'assigned',
                                    'rider_id' => auth()->id(),
                                ]);

                                Notification::make()
                                    ->success()
                                    ->title('Delivery Accepted')
                                    ->body('You have successfully accepted this delivery.')
                                    ->send();

                                auth()->user()->notify(new DeliveryAssignedNotification($record));

                                Log::info('Delivery accepted by rider', [
                                    'delivery_id' => $record->id,
                                    'rider_id' => auth()->id(),
                                ]);
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Failed to accept delivery: '.$e->getMessage())
                                    ->send();

                                Log::error('Failed to accept delivery', [
                                    'delivery_id' => $record->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }),

                    // Mark as Picked Up
                    Action::make('mark_picked_up')
                        ->icon('heroicon-o-truck')
                        ->color('info')
                        ->label('Mark Picked Up')
                        ->visible(fn (Delivery $record) => $record->status === 'assigned')
                        ->form([
                            DateTimePicker::make('actual_pickup')
                                ->label('Pickup Time')
                                ->default(now())
                                ->required()
                                ->native(false),
                            Textarea::make('pickup_notes')
                                ->label('Pickup Notes (Optional)')
                                ->rows(3)
                                ->placeholder('Any notes about the pickup...'),
                        ])
                        ->action(function (Delivery $record, array $data) {
                            try {
                                // Update delivery status
                                $record->update([
                                    'status' => 'picked_up',
                                    'actual_pickup' => $data['actual_pickup'],
                                    'delivery_notes' => $data['pickup_notes'] ?? null,
                                ]);

                              
                                $orders = $record->orders;
                                foreach ($orders as $order) {
                                    // Only update if order is in a status that can be shipped
                                    if (in_array($order->status, ['confirmed', 'processing'])) {
                                        $order->update(['status' => 'shipped']);
                                        
                                        Log::info('Order marked as shipped', [
                                            'order_id' => $order->id,
                                            'delivery_id' => $record->id,
                                        ]);
                                    }
                                }

                                Notification::make()
                                    ->success()
                                    ->title('Pickup Confirmed')
                                    ->body('The delivery has been marked as picked up.')
                                    ->send();

                                auth()->user()->notify(new DeliveryStatusUpdatedNotification(
                                    $record,
                                    'assigned',
                                    'picked_up'
                                ));

                                Log::info('Delivery picked up by rider', [
                                    'delivery_id' => $record->id,
                                    'rider_id' => auth()->id(),
                                    'orders_count' => $orders->count(),
                                ]);
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Failed to mark as picked up: '.$e->getMessage())
                                    ->send();

                                Log::error('Failed to mark delivery as picked up', [
                                    'delivery_id' => $record->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }),

                    // Mark as In Transit
                    Action::make('mark_in_transit')
                        ->icon('heroicon-o-truck')
                        ->color('primary')
                        ->label('Mark In Transit')
                        ->visible(fn (Delivery $record) => $record->status === 'picked_up')
                        ->requiresConfirmation()
                        ->modalHeading('Mark as In Transit')
                        ->modalDescription('Confirm that you are now en route to the delivery destination.')
                        ->action(function (Delivery $record) {
                            try {
                                // Update delivery status
                                $record->update([
                                    'status' => 'in_transit',
                                ]);

                                Notification::make()
                                    ->success()
                                    ->title('In Transit')
                                    ->body('The delivery status has been updated to in transit.')
                                    ->send();

                                auth()->user()->notify(new DeliveryStatusUpdatedNotification(
                                    $record,
                                    'picked_up',
                                    'in_transit'
                                ));

                                Log::info('Delivery marked as in transit', [
                                    'delivery_id' => $record->id,
                                    'rider_id' => auth()->id(),
                                ]);
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Failed to update status: '.$e->getMessage())
                                    ->send();

                                Log::error('Failed to mark delivery as in transit', [
                                    'delivery_id' => $record->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }),

                    // Mark as Delivered
                    Action::make('mark_delivered')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->label('Mark Delivered')
                        ->visible(fn (Delivery $record) => in_array($record->status, ['picked_up', 'in_transit']))
                        ->form([
                            DateTimePicker::make('actual_delivery')
                                ->label('Delivery Time')
                                ->default(now())
                                ->required()
                                ->native(false),
                            FileUpload::make('proof_of_delivery')
                                ->label('Proof of Delivery')
                                ->image()
                                ->maxSize(5120)
                                ->directory('proof-of-delivery')
                                ->visibility('private')
                                ->helperText('Upload a photo of the delivered items or recipient signature.'),
                            Textarea::make('delivery_notes')
                                ->label('Delivery Notes')
                                ->rows(3)
                                ->placeholder('Any notes about the delivery...')
                                ->helperText('Include recipient name, any special instructions followed, etc.'),
                        ])
                        ->action(function (Delivery $record, array $data) {
                            try {
                                // Update delivery
                                $record->update([
                                    'status' => 'delivered',
                                    'actual_delivery' => $data['actual_delivery'],
                                    'proof_of_delivery' => $data['proof_of_delivery'] ?? null,
                                    'delivery_notes' => $data['delivery_notes'] ?? null,
                                ]);

                                // Mark all related orders as delivered
                                $orders = $record->orders;
                                $deliveredCount = 0;
                                
                                foreach ($orders as $order) {
                                    // Only mark as delivered if the order can be delivered
                                    if (in_array($order->status, ['confirmed', 'processing', 'shipped'])) {
                                        if (method_exists($order, 'markDelivered')) {
                                            $order->markDelivered([
                                                'proof' => $data['proof_of_delivery'] ?? null,
                                            ]);
                                        } else {
                                            // Fallback to simple status update
                                            $order->update([
                                                'status' => 'delivered',
                                                'delivered_at' => $data['actual_delivery'],
                                            ]);
                                        }
                                        
                                        $deliveredCount++;
                                        
                                        Log::info('Order marked as delivered', [
                                            'order_id' => $order->id,
                                            'order_number' => $order->order_number,
                                            'delivery_id' => $record->id,
                                        ]);
                                    }
                                }

                                Notification::make()
                                    ->success()
                                    ->title('Delivery Completed')
                                    ->body("The delivery has been marked as delivered. {$deliveredCount} order(s) completed.")
                                    ->send();

                                auth()->user()->notify(new DeliveryStatusUpdatedNotification(
                                    $record,
                                    $record->getOriginal('status'),
                                    'delivered'
                                ));

                                Log::info('Delivery completed by rider', [
                                    'delivery_id' => $record->id,
                                    'rider_id' => auth()->id(),
                                    'orders_delivered' => $deliveredCount,
                                    'total_orders' => $orders->count(),
                                ]);
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Failed to complete delivery: '.$e->getMessage())
                                    ->send();

                                Log::error('Failed to complete delivery', [
                                    'delivery_id' => $record->id,
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(),
                                ]);
                            }
                        }),

                    // Decline/Cancel Delivery
                    Action::make('decline')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->label('Decline Delivery')
                        ->visible(fn (Delivery $record) => in_array($record->status, ['pending', 'assigned']))
                        ->form([
                            Textarea::make('cancellation_reason')
                                ->label('Reason for Declining')
                                ->required()
                                ->rows(4)
                                ->placeholder('Please provide a reason for declining this delivery...')
                                ->helperText('This will help us improve our service.'),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Decline Delivery')
                        ->modalDescription('Are you sure you want to decline this delivery? It will be reassigned to another rider.')
                        ->action(function (Delivery $record, array $data) {
                            try {
                                $record->update([
                                    'status' => 'pending',
                                    'rider_id' => null,
                                    'delivery_notes' => $record->delivery_notes
                                        ? $record->delivery_notes."\n\nDeclined by Rider: ".$data['cancellation_reason']
                                        : 'Declined by Rider: '.$data['cancellation_reason'],
                                ]);

                                Notification::make()
                                    ->warning()
                                    ->title('Delivery Declined')
                                    ->body('The delivery has been declined and will be reassigned.')
                                    ->send();

                                auth()->user()->notify(new DeliveryStatusUpdatedNotification(
                                    $record,
                                    'assigned',
                                    'pending'
                                ));

                                Log::info('Delivery declined by rider', [
                                    'delivery_id' => $record->id,
                                    'rider_id' => auth()->id(),
                                    'reason' => $data['cancellation_reason'],
                                ]);
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Failed to decline delivery: '.$e->getMessage())
                                    ->send();
                            }
                        }),

                    // Report Issue
                    Action::make('report_issue')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('warning')
                        ->label('Report Issue')
                        ->visible(fn (Delivery $record) => in_array($record->status, ['assigned', 'picked_up']))
                        ->form([
                            Textarea::make('issue_description')
                                ->label('Describe the Issue')
                                ->required()
                                ->rows(4)
                                ->placeholder('Describe the issue you encountered...'),
                            FileUpload::make('issue_photo')
                                ->label('Photo Evidence (Optional)')
                                ->image()
                                ->maxSize(5120)
                                ->directory('delivery-issues')
                                ->visibility('private'),
                        ])
                        ->action(function (Delivery $record, array $data) {
                            try {
                                $issueNote = "\n\n[ISSUE REPORTED] ".now()->format('Y-m-d H:i:s')."\n".$data['issue_description'];
                                if (isset($data['issue_photo'])) {
                                    $issueNote .= "\nPhoto: ".$data['issue_photo'];
                                }

                                $record->update([
                                    'delivery_notes' => $record->delivery_notes.$issueNote,
                                ]);

                                // TODO::Notify operations team

                                Notification::make()
                                    ->success()
                                    ->title('Issue Reported')
                                    ->body('Your issue has been reported to the operations team.')
                                    ->send();

                                Log::warning('Delivery issue reported', [
                                    'delivery_id' => $record->id,
                                    'rider_id' => auth()->id(),
                                    'issue' => $data['issue_description'],
                                ]);
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Failed to report issue: '.$e->getMessage())
                                    ->send();
                            }
                        }),

                
                ])
                    ->label('Actions')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->size('sm')->outlined()
                    ->button(),
            ])
            ->bulkActions([

            ])
            ->emptyStateHeading('No Deliveries Assigned')
            ->emptyStateDescription('You don\'t have any deliveries assigned yet.')
            ->emptyStateIcon('heroicon-o-truck');
    }
}