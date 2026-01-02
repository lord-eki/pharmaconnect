<?php

namespace App\Filament\Operation\Resources\Orders\Tables;

use App\Jobs\BulkSendOrdersToSupplierJob;
use App\Jobs\GenerateInvoicePdfJob;
use App\Jobs\SendInvoiceEmailJob;
use App\Models\Order;
use App\Services\OrderReportService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->copyable()
                    ->copyMessage('Order number copied')
                    ->tooltip('Click to copy'),

                TextColumn::make('prescription.prescription_number')
                    ->label('Prescription')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->icon('heroicon-m-document-text'),

                TextColumn::make('supplier.company_name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->supplier?->phone)
                    ->wrap(),

                TextColumn::make('prescription.patient.full_name')
                    ->label('Patient')
                    ->searchable()
                    ->toggleable()
                    ->wrap(),

                TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->summarize([
                        Sum::make()
                            ->money('KES')
                            ->label('Total'),
                    ]),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending_review',
                        'info' => 'sent_to_supplier',
                        'primary' => ['confirmed', 'processing', 'shipped','sent_to_supplier'],
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ])
                    ->icons([
                        'heroicon-m-clock' => 'pending_review',
                        'heroicon-m-paper-airplane' => 'sent_to_supplier',
                        'heroicon-m-check-circle' => 'confirmed',
                        'heroicon-m-arrow-path' => 'processing',
                        'heroicon-m-truck' => 'shipped',
                        'heroicon-m-check-badge' => 'delivered',
                        'heroicon-m-x-circle' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending_review' => 'Pending Review',
                        'sent_to_supplier' => 'Sent to Supplier',
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('ordered_at')
                    ->label('Ordered')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->since(),

                TextColumn::make('sent_to_supplier_at')
                    ->label('Sent to Supplier')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Not sent')
                    ->description(fn ($record) => $record->sent_to_supplier_at
                        ? $record->sent_to_supplier_at->diffForHumans()
                        : null),

                TextColumn::make('expected_delivery')
                    ->label('Expected Delivery')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_overdue')
                    ->label('Overdue')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->toggleable()
                    ->tooltip(fn ($record) => $record->is_overdue
                        ? 'This order is overdue!'
                        : 'On track'),

                TextColumn::make('delivery.status')
                    ->label('Delivery Status')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('No delivery'),
            ])->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Order Status')
                    ->options([
                        'pending_review' => 'Pending Review',
                        'sent_to_supplier' => 'Sent to Supplier',
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple()
                    ->preload(),

                SelectFilter::make('supplier')
                    ->relationship('supplier', 'company_name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Filter::make('overdue')
                    ->label('Overdue Orders')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('expected_delivery', '<', now())
                        ->whereNotIn('status', ['delivered', 'cancelled']))
                    ->toggle(),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('ordered_from')
                            ->label('Ordered from'),
                        DatePicker::make('ordered_until')
                            ->label('Ordered until'),
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
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['ordered_from'] ?? null) {
                            $indicators[] = Indicator::make('Ordered from '.\Carbon\Carbon::parse($data['ordered_from'])->toFormattedDateString())
                                ->removeField('ordered_from');
                        }
                        if ($data['ordered_until'] ?? null) {
                            $indicators[] = Indicator::make('Ordered until '.\Carbon\Carbon::parse($data['ordered_until'])->toFormattedDateString())
                                ->removeField('ordered_until');
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),

                    Action::make('download_lpo')
                        ->label('Download LPO')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (Order $record) {
                            try {
                                $reportService = app(OrderReportService::class);

                                // Generate the PDF and store it temporarily
                                $path = $reportService->generateLPO($record);

                                // Create a download URL
                                $url = Storage::url($path);

                                // Notify user with download link
                                Notification::make()
                                    ->title('LPO Ready')
                                    ->body('Your LPO has been generated successfully.')
                                    ->success()
                                    ->actions([
                                        Action::make('download')
                                            ->label('Download PDF')
                                            ->url($url)
                                            ->openUrlInNewTab(),
                                    ])
                                    ->persistent()
                                    ->send();

                            } catch (\Exception $e) {
                                \Log::error('LPO Generation Error', [
                                    'order_id' => $record->id,
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(),
                                ]);

                                Notification::make()
                                    ->title('Error generating LPO')
                                    ->body('Unable to generate PDF. Please try again or contact support.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation(false)
                        ->tooltip('Download Local Purchase Order as PDF'),

                    Action::make('generate_invoice')
                        ->label('Generate Invoice')
                        ->icon('heroicon-o-document-currency-dollar')
                        ->color('info')
                        ->visible(fn (Order $record): bool => $record->isEligibleForInvoice())
                        ->form([
                            DatePicker::make('due_date')
                                ->label('Due Date')
                                ->default(now()->addDays(30))
                                ->required()
                                ->minDate(now()),

                            \Filament\Forms\Components\TextInput::make('discount_amount')
                                ->label('Discount Amount (Optional)')
                                ->numeric()
                                ->prefix('KES')
                                ->default(0)
                                ->minValue(0),

                            Textarea::make('notes')
                                ->label('Invoice Notes (Optional)')
                                ->rows(3),
                        ])
                        ->action(function (Order $record, array $data): void {
                            try {
                                $invoiceService = app(\App\Services\InvoiceService::class);

                                // Generate invoice synchronously (this is quick)
                                $invoice = $invoiceService->createInvoiceFromOrder($record, [
                                    'discount_amount' => $data['discount_amount'] ?? 0,
                                    'currency' => 'KES',
                                    'due_date' => $data['due_date'],
                                    'notes' => $data['notes'] ?? null,
                                ]);

                                // Generate PDF asynchronously
                                GenerateInvoicePdfJob::dispatch($invoice);

                                Notification::make()
                                    ->title('Invoice Generated')
                                    ->body("Invoice {$invoice->invoice_number} is being processed. PDF will be available shortly.")
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                \Log::error('Invoice Generation Error', [
                                    'order_id' => $record->id,
                                    'error' => $e->getMessage(),
                                ]);

                                Notification::make()
                                    ->title('Error generating invoice')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Generate Insurance Invoice')
                        ->modalDescription('This will create an invoice for the insurance company.')
                        ->modalSubmitActionLabel('Generate Invoice')
                        ->tooltip('Generate invoice for insurance'),

                    Action::make('send_invoice')
                        ->label('Send Invoice')
                        ->icon('heroicon-o-envelope')
                        ->color('success')
                        ->visible(fn (Order $record): bool => $record->invoices()->exists())
                        ->form([
                            \Filament\Forms\Components\TagsInput::make('cc')
                                ->label('CC Email Addresses')
                                ->placeholder('Add email addresses to CC'),

                            Textarea::make('message')
                                ->label('Additional Message (Optional)')
                                ->rows(3),
                        ])
                        ->action(function (Order $record, array $data): void {
                            try {
                                $invoice = $record->invoices()->latest()->first();

                                // Dispatch job to send email asynchronously
                                SendInvoiceEmailJob::dispatch($invoice, [
                                    'cc' => $data['cc'] ?? [],
                                    'message' => $data['message'] ?? null,
                                ]);

                                Notification::make()
                                    ->title('Invoice Queued for Sending')
                                    ->body("Invoice {$invoice->invoice_number} will be sent to {$invoice->order->prescription->patient->insuranceProvider->company_name} shortly.")
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                \Log::error('Invoice Send Error', [
                                    'order_id' => $record->id,
                                    'error' => $e->getMessage(),
                                ]);

                                Notification::make()
                                    ->title('Error queuing invoice')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Send Invoice to Insurance')
                        ->modalDescription(fn (Order $record) => 'Send invoice to '.$record->prescription->patient->insuranceProvider?->company_name
                        )
                        ->modalSubmitActionLabel('Send Email')
                        ->tooltip('Email invoice to insurance company'),

                    Action::make('download_invoice')
                        ->label('Download Invoice')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('primary')
                        ->visible(fn (Order $record): bool => $record->invoices()->exists())
                        ->action(function (Order $record) {
                            try {
                                $invoice = $record->invoices()->latest()->first();

                                // Check if PDF already exists
                                $existingPath = 'reports/invoices/Invoice_'.
                                               preg_replace('/[^A-Za-z0-9_-]/', '_', $invoice->invoice_number).
                                               '_*.pdf';

                                $files = Storage::disk('public')->files('reports/invoices');
                                $matchingFile = collect($files)->first(function ($file) use ($invoice) {
                                    return str_contains($file, preg_replace('/[^A-Za-z0-9_-]/', '_', $invoice->invoice_number));
                                });

                                if ($matchingFile) {
                                    // PDF exists, provide immediate download
                                    $url = Storage::url($matchingFile);

                                    Notification::make()
                                        ->title('Invoice PDF Ready')
                                        ->actions([
                                            Action::make('download')
                                                ->label('Download PDF')
                                                ->url($url)
                                                ->openUrlInNewTab(),
                                        ])
                                        ->persistent()
                                        ->send();
                                } else {
                                    GenerateInvoicePdfJob::dispatch($invoice);

                                    Notification::make()
                                        ->title('Generating PDF')
                                        ->body('Your invoice PDF is being generated. Please check back in a moment.')
                                        ->info()
                                        ->send();
                                }

                            } catch (\Exception $e) {
                                \Log::error('Invoice PDF Error', [
                                    'order_id' => $record->id,
                                    'error' => $e->getMessage(),
                                ]);

                                Notification::make()
                                    ->title('Error with invoice PDF')
                                    ->body('Unable to process PDF request. Please try again.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation(false)
                        ->tooltip('Download invoice as PDF'),

                    Action::make('cancel')
                        ->label('Cancel')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Order $record): bool => in_array($record->status, ['pending_review', 'sent_to_supplier', 'confirmed']))
                        ->form([
                            Textarea::make('reason')
                                ->label('Cancellation Reason')
                                ->required()
                                ->rows(3)
                                ->helperText('Please provide a reason for cancelling this order'),
                        ])
                        ->action(function (Order $record, array $data): void {
                            try {
                                $record->cancel($data['reason']);

                                Notification::make()
                                    ->title('Order cancelled')
                                    ->body('The order has been cancelled successfully')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error cancelling order')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Order')
                        ->modalDescription('Are you sure you want to cancel this order? This action cannot be undone.')
                        ->modalSubmitActionLabel('Cancel Order'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('send_to_supplier')
                        ->label('Send to Suppliers')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->form([
                            Textarea::make('notes')
                                ->label('Notes for Supplier (Optional)')
                                ->rows(3)
                                ->helperText('These notes will be added to all selected orders'),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            try {
                                // Filter only pending_review orders
                                $eligibleOrders = $records->filter(fn ($order) => $order->status === 'pending_review');

                                if ($eligibleOrders->isEmpty()) {
                                    Notification::make()
                                        ->title('No Eligible Orders')
                                        ->body('Only orders in "Pending Review" status can be sent to suppliers.')
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                $orderIds = $eligibleOrders->pluck('id')->toArray();

                                BulkSendOrdersToSupplierJob::dispatch(
                                    $orderIds,
                                    $data['notes'] ?? null,
                                    auth()->id()
                                );

                                Notification::make()
                                    ->title('Orders Being Sent')
                                    ->body("Processing {$eligibleOrders->count()} orders. Suppliers will be notified shortly.")
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                \Log::error('Bulk Send to Supplier Error', [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(),
                                ]);

                                Notification::make()
                                    ->title('Error Processing Orders')
                                    ->body('Unable to send orders. Please try again.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Send Orders to Suppliers')
                        ->modalDescription('This will send all selected pending review orders to their respective suppliers for processing.')
                        ->modalSubmitActionLabel('Send to Suppliers'),
                    BulkAction::make('generate_invoices')
                        ->label('Generate Invoices')
                        ->icon('heroicon-o-document-currency-dollar')
                        ->color('info')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            try {
                                // Filter only eligible orders
                                $eligibleOrders = $records->filter(fn ($order) => $order->isEligibleForInvoice());

                                if ($eligibleOrders->isEmpty()) {
                                    Notification::make()
                                        ->title('No Eligible Orders')
                                        ->body('None of the selected orders are eligible for invoicing.')
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                $orderIds = $eligibleOrders->pluck('id')->toArray();

                                // Dispatch job to process in background
                                \App\Jobs\BulkGenerateInvoicesJob::dispatch(
                                    $orderIds,
                                    auth()->id()
                                );

                                Notification::make()
                                    ->title('Invoice Generation Started')
                                    ->body("Processing {$eligibleOrders->count()} orders. You'll be notified when complete.")
                                    ->info()
                                    ->send();

                            } catch (\Exception $e) {
                                \Log::error('Bulk Invoice Generation Error', [
                                    'error' => $e->getMessage(),
                                ]);

                                Notification::make()
                                    ->title('Error starting invoice generation')
                                    ->body('Unable to queue invoices. Please try again.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Generate Multiple Invoices')
                        ->modalDescription('This will queue invoice generation for all selected eligible orders.')
                        ->modalSubmitActionLabel('Generate Invoices'),

                    BulkAction::make('send_invoices')
                        ->label('Send Invoices to Insurance')
                        ->icon('heroicon-o-envelope')
                        ->color('success')
                        ->form([
                            Textarea::make('message')
                                ->label('Message for All Insurance Companies')
                                ->rows(3),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            try {
                                // Get invoice IDs from selected orders
                                $invoiceIds = $records->flatMap(fn ($order) => $order->invoices->pluck('id'))->toArray();

                                if (empty($invoiceIds)) {
                                    Notification::make()
                                        ->title('No Invoices Found')
                                        ->body('None of the selected orders have invoices. Generate invoices first.')
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                // Dispatch job to send emails in background
                                \App\Jobs\BulkSendInvoicesJob::dispatch(
                                    $invoiceIds,
                                    ['message' => $data['message'] ?? null],
                                    auth()->id()
                                );

                                Notification::make()
                                    ->title('Invoice Sending Started')
                                    ->body('Processing '.count($invoiceIds).' invoices. Emails will be sent shortly.')
                                    ->info()
                                    ->send();

                            } catch (\Exception $e) {
                                \Log::error('Bulk Invoice Send Error', [
                                    'error' => $e->getMessage(),
                                ]);

                                Notification::make()
                                    ->title('Error starting bulk send')
                                    ->body('Unable to queue emails. Please try again.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Send Multiple Invoices')
                        ->modalDescription('This will queue emails to all insurance companies for selected orders.')
                        ->modalSubmitActionLabel('Send Emails'),

                ]),
            ])->emptyStateHeading('No orders yet')
            ->emptyStateDescription('Orders will appear here once prescriptions are submitted.')
            ->emptyStateIcon('heroicon-o-shopping-bag');
    }
}
