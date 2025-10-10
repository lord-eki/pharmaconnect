<?php

namespace App\Filament\Physician\Resources\Physician\Commissions\Tables;

use App\Models\Commission;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\FontWeight;
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
         TextColumn::make('created_at')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable(),

               TextColumn::make('prescription.prescription_number')
                    ->label('Prescription #')
                    ->searchable()
                    ->copyable()
                    // ->url(fn (Commission $record): string => 
                    //     route('filament.physician.resources.prescriptions.view', $record->prescription))
                    ->color('primary'),

               TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable()
                    ->copyable()
                    // ->url(fn (Commission $record): string => 
                    //     route('filament.physician.resources.orders.view', $record->order))
                    ->color('info'),

               TextColumn::make('prescription.patient.full_name')
                    ->label('Patient')
                    ->searchable(['first_name', 'last_name']),

               TextColumn::make('gross_amount')
                    ->label('Order Amount')
                    ->money('KES')
                    ->sortable(),

               TextColumn::make('commission_rate')
                    ->label('Rate')
                    ->suffix('%')
                    ->sortable(),

               TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->money('KES')
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->color('success'),

               BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'approved',
                        'success' => 'paid',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'approved',
                        'heroicon-o-banknotes' => 'paid',
                    ]),

                TextColumn::make('approved_at')
                    ->label('Approved')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Pending'),

                TextColumn::make('paid_at')
                    ->label('Paid')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Pending'),
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
