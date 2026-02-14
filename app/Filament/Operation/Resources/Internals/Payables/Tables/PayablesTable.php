<?php

namespace App\Filament\Operation\Resources\Internals\Payables\Tables;

use App\Filament\Exports\PayableExporter;
use App\Models\Commission;
use App\Models\Payable;
use App\Models\Payment;
use Auth;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PayablesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('created_at')
                    ->dateTime()->label('Date')
                    ->sortable(),

                TextColumn::make('reference')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('order.order_number')
                    ->label('Order')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('vendor_type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'supplier',
                        'success' => 'physician',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('amount')
                    ->money('KES')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? ucwords(str_replace('_', ' ', $state)) : 'N/A'),

                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),

                IconColumn::make('paid_at')
                    ->label('Paid')
                    ->boolean()
                    ->sortable(),

            ])
            ->filters([
                SelectFilter::make('vendor_type')
                    ->options([
                        'supplier' => 'Supplier',
                        'physician' => 'Physician',
                    ]),

                Filter::make('paid')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('paid_at'))
                    ->label('Paid'),

                Filter::make('unpaid')
                    ->query(fn (Builder $query): Builder => $query->whereNull('paid_at'))
                    ->label('Unpaid'),

                Filter::make('overdue')
                    ->query(fn (Builder $query): Builder => $query->whereNull('paid_at')
                        ->where('due_date', '<', now())
                    )
                    ->label('Overdue'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('mark_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Payable $record) => ! $record->paid_at)
                        ->form([
                            Select::make('payment_method')
                                ->options([
                                    'mpesa' => 'M-Pesa',
                                    'bank_transfer' => 'Bank Transfer',
                                    'cheque' => 'Cheque',
                                ])
                                ->required(),
                            TextInput::make('gateway_reference')
                                ->label('Transaction Reference'),
                            DateTimePicker::make('paid_at')
                                ->label('Payment Date')
                                ->default(now())
                                ->required(),
                        ])
                        ->action(function (Payable $record, array $data) {
                            DB::transaction(function () use ($record, $data) {


                                // Update the payable record
                                $record->update($data);

                                // Update related transaction if exists
                                if ($record->transaction) {
                                    $record->transaction()->update([
                                        'status' => 'completed',
                                        'completed_at' => $data['paid_at'],
                                    ]);
                                }

                                // Handle physician commissions
                                if ($record->vendor_type === 'physician' && $record->order_id) {
                                    $commission = Commission::where('order_id', $record->order_id)
                                        ->where('physician_id', $record->vendor_id)
                                        ->whereIn('status', ['pending', 'approved'])
                                        ->first();

                                    if ($commission) {
                                        if ($commission->status === 'pending') {
                                            $commission->approve(auth()->id());
                                        }

                                        $commission->processPayout($data['gateway_reference'] ?? 'MANUAL-'.now()->timestamp);
                                    }
                                }

                                if ($record->vendor_type === 'supplier') {
                                    Payment::create([
                                        'payment_reference' => $data['gateway_reference'] ?? 'MANUAL-'.now()->timestamp,
                                        'payee_id' => $record->vendor_id,
                                        'payer_id' => Auth::id(),
                                        'order_id' => $record->order_id,
                                        'amount' => $record->amount,
                                        'currency' => 'KES',
                                        'payment_method' => $data['payment_method'],
                                        'status' => 'completed',
                                        'processed_at' => $data['paid_at'],
                                        'notes' => "Supplier payment for order {$record->order->order_number}",
                                    ]);
                                }
                            });

                            Notification::make()
                                ->success()
                                ->title('Payment Recorded')
                                ->body("The {$record->vendor_type} payable has been marked as paid.")
                                ->send();
                        }),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(PayableExporter::class)
                    ->label('Export')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])->defaultSort('created_at', 'desc');
    }
}