<?php

namespace App\Filament\Physician\Resources\Physician\Orders\Tables;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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
                    ->weight('bold'),

                TextColumn::make('prescription.prescription_number')
                    ->label('Prescription #')
                    ->searchable()
                    // ->url(fn (Order $record): string => 
                    //     route('filament.physician.resources.prescriptions.view', $record->prescription))
                    ->color('primary'),

                TextColumn::make('prescription.patient.full_name')
                    ->label('Patient')
                    ->searchable(['first_name', 'last_name'])
                    ->description(fn (Order $record): string => 
                        $record->prescription->patient->patient_number),

                TextColumn::make('supplier.company_name')
                    ->label('Supplier')
                    ->searchable(),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable()
                    ->weight('bold'),

                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'confirmed',
                        'primary' => 'shipped',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'confirmed',
                        'heroicon-o-arrow-path' => 'processing',
                        'heroicon-o-truck' => 'shipped',
                        'heroicon-o-check-badge' => 'delivered',
                        'heroicon-o-x-circle' => 'cancelled',
                    ]),

                IconColumn::make('is_overdue')
                    ->label('Overdue')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                TextColumn::make('ordered_at')
                    ->label('Ordered')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
,
                TextColumn::make('expected_delivery')
                    ->label('Expected')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                TextColumn::make('delivered_at')
                    ->label('Delivered')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->placeholder('Pending'),
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
                    ->query(fn (Builder $query): Builder => 
                        $query->where('expected_delivery', '<', now())
                              ->whereNotIn('status', ['delivered', 'cancelled']))
                    ->label('Overdue Orders')
                    ->toggle(),

               Filter::make('ordered_at')
                    ->form([
                        DatePicker::make('from')
                            ->label('From Date'),
                        DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('ordered_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('ordered_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
               ViewAction::make(),
                
                Action::make('track')
                    ->label('Track')
                    ->icon('heroicon-o-map-pin')
                    ->color('info')
                    ->visible(fn (Order $record) => in_array($record->status, ['shipped', 'confirmed', 'processing']))
                    // ->url(fn (Order $record): string => 
                    //     $record->delivery 
                    //         ? route('filament.physician.resources.deliveries.view', $record->delivery)
                    //         : '#')
                    ->disabled(fn (Order $record) => !$record->delivery),
            ])
              ->defaultSort('ordered_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => 
                $query->whereHas('prescription', fn ($q) => 
                    $q->where('physician_id', Auth::id())
                )
            );;
    }
}
