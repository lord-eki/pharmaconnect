<?php

namespace App\Filament\Operation\Resources\Internals\Deliveries\Tables;

use App\Jobs\BulkGenerateDeliveryNotesJob;
use App\Jobs\SendDeliveryNoteEmailJob;
use App\Models\Rider;
use App\Services\DeliveryNoteService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class DeliveriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('delivery_number')
                    ->sortable()->searchable(),

                TextColumn::make('rider.full_name')
                    ->sortable()
                    ->default('Not Assigned')
                    ->badge()
                    ->color(fn ($state) => $state === 'Not Assigned' ? 'danger' : 'success'),

                TextColumn::make('status')
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

                IconColumn::make('delivery_note_document_id')
                    ->label('Note')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-document')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => $record->delivery_note_document_id ? 'Delivery note generated' : 'No delivery note'),

                TextColumn::make('delivery_address')
                    ->limit(30),

                TextColumn::make('estimated_delivery')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                TextColumn::make('delivery_fee')
                    ->money('KES')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'assigned' => 'Assigned',
                        'ready_for_pickup' => 'Ready for Pickup',
                        'picked_up' => 'Picked Up',
                        'in_transit' => 'In Transit',
                        'delivered' => 'Delivered',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('rider')
                    ->relationship('rider', 'last_name')
                    ->preload(),

                Filter::make('unassigned')
                    ->query(fn (Builder $query): Builder => $query->whereNull('rider_id'))
                    ->label('Unassigned Only')
                    ->toggle(),

                Filter::make('has_delivery_note')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('delivery_note_document_id'))
                    ->label('Has Delivery Note')
                    ->toggle(),

                Filter::make('no_delivery_note')
                    ->query(fn (Builder $query): Builder => $query->whereNull('delivery_note_document_id')->where('status', 'delivered'))
                    ->label('Delivered Without Note')
                    ->toggle(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('assign_rider')
                        ->label('Assign')
                        ->icon('heroicon-o-user-plus')
                        ->color('primary')
                        ->visible(fn ($record) => ! $record->rider_id && in_array($record->status, ['pending']))
                        ->modalHeading('Assign Rider to Delivery')
                        ->modalDescription(fn ($record) => "Delivery: {$record->delivery_number}")
                        ->form([
                            Select::make('rider_id')
                                ->label('Select Rider')
                                ->options(function () {
                                    return Rider::query()
                                        ->where('is_active', true)
                                        ->orderBy('first_name')
                                        ->get()
                                        ->mapWithKeys(fn ($rider) => [
                                            $rider->id => "{$rider->full_name} - {$rider->phone}",
                                        ]);
                                })
                                ->required()
                                ->native(false)
                                ->placeholder('Choose a rider')
                                ->helperText('Select an active rider for this delivery'),
                        ])
                        ->action(function ($record, array $data) {
                            try {
                                DB::transaction(function () use ($record, $data) {
                                    $rider = Rider::findOrFail($data['rider_id']);

                                    $record->update([
                                        'rider_id' => $data['rider_id'],
                                        'status' => 'assigned',
                                        'assigned_at' => now(),
                                    ]);
                                });

                                Notification::make()
                                    ->title('Rider Assigned')
                                    ->body("Delivery {$record->delivery_number} assigned successfully.")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Assignment Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('reassign_rider')
                        ->label('Reassign')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn ($record) => $record->rider_id && in_array($record->status, ['assigned', 'ready_for_pickup']))
                        ->modalHeading('Reassign Rider')
                        ->modalDescription(fn ($record) => "Current rider: {$record->rider->full_name}")
                        ->form([
                            Select::make('rider_id')
                                ->label('New Rider')
                                ->options(function () {
                                    return Rider::query()
                                        ->where('is_active', true)
                                        ->orderBy('first_name')
                                        ->get()
                                        ->mapWithKeys(fn ($rider) => [
                                            $rider->id => "{$rider->full_name} - {$rider->phone}",
                                        ]);
                                })
                                ->required()
                                ->native(false)
                                ->helperText('This will change the assigned rider'),
                        ])
                        ->action(function ($record, array $data) {
                            try {
                                $oldRider = $record->rider;
                                $newRider = Rider::findOrFail($data['rider_id']);

                                $record->update([
                                    'rider_id' => $data['rider_id'],
                                    'assigned_at' => now(),
                                ]);

                                Notification::make()
                                    ->title('Rider Reassigned')
                                    ->body("Changed from {$oldRider->full_name} to {$newRider->full_name}")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Reassignment Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

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

                    Action::make('generate_delivery_note')
                        ->label('Generate Note')
                        ->icon('heroicon-o-document-plus')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'delivered' && ! $record->delivery_note_document_id)
                        ->form([
                            Checkbox::make('send_email')
                                ->label('Send email to patient')
                                ->default(true)
                                ->helperText('The delivery note will be sent to the patient\'s email address'),
                        ])
                        ->action(function ($record, array $data, DeliveryNoteService $service) {
                            try {
                                // Generate synchronously
                                if ($data['send_email'] ?? false) {
                                    $result = $service->generateAndSendDeliveryNote($record, auth()->user());

                                    if ($result['success']) {
                                        $message = 'Delivery note generated successfully';
                                        if ($result['email_sent']) {
                                            $message .= ' and sent to patient';
                                        }

                                        Notification::make()
                                            ->title('Success')
                                            ->body($message)
                                            ->success()
                                            ->send();
                                    } else {
                                        throw new \Exception($result['error'] ?? 'Unknown error');
                                    }
                                } else {
                                    $document = $service->generateDeliveryNote($record, auth()->user());

                                    Notification::make()
                                        ->title('Delivery Note Generated')
                                        ->body('You can now view or download the delivery note')
                                        ->success()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Generation Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('download_delivery_note')
                        ->label('Download Note')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->visible(fn ($record) => $record->delivery_note_document_id)
                        ->action(function ($record) {
                            try {
                                $document = $record->deliveryNoteDocument;

                                if (! $document) {
                                    throw new \Exception('Delivery note document not found');
                                }

                                $filePath = Storage::path($document->file_path);

                                if (! file_exists($filePath)) {
                                    throw new \Exception('Delivery note file not found on storage');
                                }

                                // Log the download
                                $document->logAccess(
                                    auth()->user(),
                                    'downloaded',
                                    ['ip' => request()->ip()]
                                );

                                return response()->download(
                                    $filePath,
                                    "Delivery_Note_{$record->delivery_number}.pdf",
                                    [
                                        'Content-Type' => 'application/pdf',
                                    ]
                                );
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Download Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('send_delivery_note')
                        ->label('Send Note')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->visible(fn ($record) => $record->status === 'delivered' && $record->delivery_note_document_id)
                        ->requiresConfirmation()
                        ->modalHeading('Send Delivery Note')
                        ->modalDescription(fn ($record) => "Send delivery note to patient's email")
                        ->action(function ($record) {
                            try {
                                SendDeliveryNoteEmailJob::dispatch($record, auth()->user());

                                Notification::make()
                                    ->title('Email Queued')
                                    ->body('Delivery note email is being sent in the background. You\'ll be notified when it\'s sent.')
                                    ->info()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Queue Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('view_delivery_note')
                        ->label('View Note')
                        ->icon('heroicon-o-document-magnifying-glass')
                        ->color('gray')
                        ->visible(fn ($record) => $record->delivery_note_document_id)
                        ->url(fn ($record) => $record->deliveryNoteDocument
                            ? route('delivery-note.view', ['document' => $record->deliveryNoteDocument->id])
                            : null)
                        ->openUrlInNewTab(),

                    Action::make('view_details')
                        ->label('Details')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn ($record) => route('filament.Operation.resources.internals.deliveries.view', ['record' => $record->id]))
                        ->openUrlInNewTab(false),
                ])->label('Actions')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->size('sm')->color('gray')->outlined()
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_assign_rider')
                        ->label('Assign Rider')
                        ->icon('heroicon-o-user-group')
                        ->color('primary')
                        ->modalHeading('Bulk Assign Rider')
                        ->modalDescription('Assign the same rider to all selected deliveries')
                        ->form([
                            Select::make('rider_id')
                                ->label('Select Rider')
                                ->options(function () {
                                    return Rider::query()
                                        ->where('is_active', true)
                                        ->orderBy('first_name')
                                        ->get()
                                        ->mapWithKeys(fn ($rider) => [
                                            $rider->id => "{$rider->full_name} - {$rider->phone}",
                                        ]);
                                })
                                ->searchable()
                                ->required()
                                ->native(false)
                                ->helperText('All selected unassigned deliveries will be assigned to this rider'),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $assignedCount = 0;
                            $skippedCount = 0;

                            foreach ($records as $record) {
                                if (! $record->rider_id && $record->status === 'pending') {
                                    try {
                                        $record->update([
                                            'rider_id' => $data['rider_id'],
                                            'status' => 'assigned',
                                            'assigned_at' => now(),
                                        ]);
                                        $assignedCount++;
                                    } catch (\Exception $e) {
                                        $skippedCount++;
                                    }
                                } else {
                                    $skippedCount++;
                                }
                            }

                            if ($assignedCount > 0) {
                                $rider = Rider::find($data['rider_id']);
                                Notification::make()
                                    ->title('Bulk Assignment Complete')
                                    ->body("{$assignedCount} deliveries assigned to {$rider->full_name}.".
                                          ($skippedCount > 0 ? " {$skippedCount} skipped." : ''))
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('No Assignments Made')
                                    ->body('Selected deliveries are already assigned or not in pending status.')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('bulk_generate_delivery_notes')
                        ->label('Generate Delivery Notes')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('success')
                        ->modalHeading('Bulk Generate Delivery Notes')
                        ->modalDescription('Generate delivery notes for all selected delivered deliveries')
                        ->form([
                            Checkbox::make('send_emails')
                                ->label('Send emails to patients')
                                ->default(false)
                                ->helperText('Delivery notes will be sent to each patient\'s email address'),
                            Checkbox::make('use_queue')
                                ->label('Process in background (recommended for large batches)')
                                ->default(true)
                                ->helperText('Generate notes in the background to avoid timeouts'),
                        ])
                        ->action(function (Collection $records, array $data, DeliveryNoteService $service) {
                            $deliveries = $records
                                ->where('status', 'delivered')
                                ->whereNull('delivery_note_document_id');

                            if ($deliveries->isEmpty()) {
                                Notification::make()
                                    ->title('No Eligible Deliveries')
                                    ->body('None of the selected deliveries are eligible for note generation.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $deliveryIds = $deliveries->pluck('id')->toArray();

                            try {
                                if ($data['use_queue'] ?? true) {
                                    // Use queue for large batches
                                    BulkGenerateDeliveryNotesJob::dispatch(
                                        $deliveryIds,
                                        auth()->user(),
                                        $data['send_emails'] ?? false
                                    );

                                    Notification::make()
                                        ->title('Bulk Generation Started')
                                        ->body(count($deliveryIds).' delivery notes are being generated in the background.')
                                        ->info()
                                        ->send();
                                } else {
                                    // Generate synchronously
                                    $successCount = 0;
                                    $failCount = 0;

                                    foreach ($deliveries as $delivery) {
                                        try {
                                            if ($data['send_emails'] ?? false) {
                                                $result = $service->generateAndSendDeliveryNote($delivery, auth()->user());
                                                if ($result['success']) {
                                                    $successCount++;
                                                } else {
                                                    $failCount++;
                                                }
                                            } else {
                                                $service->generateDeliveryNote($delivery, auth()->user());
                                                $successCount++;
                                            }
                                        } catch (\Exception $e) {
                                            $failCount++;
                                        }
                                    }

                                    $message = "{$successCount} delivery notes generated successfully";
                                    if ($failCount > 0) {
                                        $message .= ", {$failCount} failed";
                                    }

                                    Notification::make()
                                        ->title('Bulk Generation Complete')
                                        ->body($message)
                                        ->success()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Generation Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
