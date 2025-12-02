<?php

namespace App\Filament\Insurer\Resources\Insurance\InsurerOrders\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InsurerOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ordered_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('prescription.patient.full_name')
                    ->label('Patient')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('prescription.insuranceClaim.claim_number')
                    ->label('Claim #')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('KES')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => fn ($state) => in_array($state, ['confirmed', 'processing']),
                        'primary' => 'shipped',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ]),

                TextColumn::make('delivered_at')
                    ->label('Delivered')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    ]),
                    
                Filter::make('ordered_today')
                    ->query(fn (Builder $query) => $query->whereDate('ordered_at', today()))
                    ->label('Ordered Today'),
                    
                Filter::make('pending_delivery')
                    ->query(fn (Builder $query) => $query->whereIn('status', ['confirmed', 'processing', 'shipped']))
                    ->label('Pending Delivery'),
            ])
            ->recordActions([
                ViewAction::make(),
                
                EditAction::make()
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'confirmed'])),
                    
                Action::make('track')
                    ->icon('heroicon-o-map-pin')
                    ->color('info')
                    ->visible(fn ($record) => in_array($record->status, ['shipped', 'processing'])),
                    // ->url(fn ($record) => $record->delivery ? 
                    //     route('filament.insurer.resources.deliveries.view', $record->delivery) : 
                    //     null
                    // ),
            ])
            ->toolbarActions([
              
            ])->defaultSort('ordered_at', 'desc')
;
    }
}
