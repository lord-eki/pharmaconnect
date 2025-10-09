<?php

namespace App\Filament\Physician\Resources\Physician\Commissions\Tables;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CommissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('prescription.prescription_number')
                    ->label('Prescription')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                    
                TextColumn::make('order.order_number')
                    ->label('Order')
                    ->searchable()
                    ->copyable(),
                    
                TextColumn::make('prescription.patient.first_name')
                    ->label('Patient')
                    ->formatStateUsing(fn ($record) => 
                        "{$record->prescription->patient->first_name} {$record->prescription->patient->last_name}"
                    )
                    ->searchable(['first_name', 'last_name']),
                    
                TextColumn::make('commission_rate')
                    ->label('Rate')
                    ->suffix('%')
                    ->sortable(),
                    
                TextColumn::make('gross_amount')
                    ->label('Order Amount')
                    ->money('KES')
                    ->sortable(),
                    
                TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->money('KES')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                    
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'approved',
                        'success' => 'paid',
                    ])
                    ->sortable(),
                    
                TextColumn::make('paid_at')
                    ->label('Payment Date')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'paid' => 'Paid',
                    ])
                    ->multiple(),
                    
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')
                            ->label('From Date'),
                        DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], 
                                fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], 
                                fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => 
                $query->where('physician_id', Auth::id())
            );
    }

    
}
