<?php

namespace App\Filament\Operation\Resources\Internals\Payables\Tables;

use App\Models\Commission;
use App\Models\Payable;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Builder;
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
                            DB::transaction(function () use ($record, $data)
                            {

                            $record->update($data);

                            if ($record->transaction) {
                                $record->transaction()->update([
                                    'status' => 'completed',
                                    'completed_at' => $data['paid_at'],
                                ]);
                            }

                            if ($record->vendor_type === 'physician' && $record->order_id) {
                                Commission::where('order_id', $record->order_id)
                                    ->where('physician_id', $record->vendor_id)
                                    ->whereIn('status', ['pending', 'approved'])
                                    ->update([
                                        'status' => 'paid',
                                        'paid_at' => $data['paid_at'],
                                        'payment_reference' => $data['gateway_reference'] ?? null,
                                    ]);
                            }
                        });

                            Notification::make()
                                ->success()
                                ->title('Payment Recorded')
                                ->body('The payable has been marked as paid.')
                                ->send();

                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])->defaultSort('created_at', 'desc');
    }
}
