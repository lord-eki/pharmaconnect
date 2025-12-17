<?php

namespace App\Filament\Operation\Resources\Orders\Tables;

use App\Models\Order;
use App\Services\OrderReportService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportBulkAction;
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
                        'primary' => ['confirmed', 'processing', 'shipped'],
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
                                'trace' => $e->getTraceAsString()
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('download_bulk_lpo')
                        ->label('Download Selected as LPO')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            try {
                                $reportService = app(OrderReportService::class);
                                $orderIds = $records->pluck('id')->toArray();
                                
                                // Generate and store the PDF
                                $path = $reportService->generateBulkLPO($orderIds);
                                
                                // Return download URL
                                $url = Storage::url($path);
                                
                                Notification::make()
                                    ->title('Bulk LPO Generated')
                                    ->body('Click the link below to download')
                                    ->success()
                                    ->actions([
                                      Action::make('download')
                                            ->label('Download PDF')
                                            ->url($url)
                                            ->openUrlInNewTab()
                                    ])
                                    ->persistent()
                                    ->send();
                                    
                            } catch (\Exception $e) {
                                \Log::error('Bulk LPO Error', [
                                    'order_ids' => $records->pluck('id')->toArray(),
                                    'error' => $e->getMessage()
                                ]);
                                
                                Notification::make()
                                    ->title('Error generating bulk LPO')
                                    ->body('Unable to generate PDF. Please try with fewer orders.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Download Bulk LPO')
                        ->modalDescription('This will generate a PDF document containing all selected orders.')
                        ->modalSubmitActionLabel('Generate PDF'),

                   
                ]),
            ])->emptyStateHeading('No orders yet')
            ->emptyStateDescription('Orders will appear here once prescriptions are submitted.')
            ->emptyStateIcon('heroicon-o-shopping-bag');
    }
}