<?php

namespace App\Filament\Rider\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RecentDeliveriesWidget extends StatsOverviewWidget
{
   protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $rider = auth()->user()->rider;

        return $table
            ->query(
                Delivery::query()
                    ->where('rider_id', $rider?->id)
                    ->whereIn('status', ['delivered', 'cancelled'])
                    ->latest('updated_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('delivery_number')
                    ->label('Delivery #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'delivered',
                        'heroicon-o-x-circle' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('recipient_name')
                    ->label('Recipient')
                    ->searchable(),

                Tables\Columns\TextColumn::make('delivery_address')
                    ->label('Address')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->delivery_address),

                Tables\Columns\TextColumn::make('actual_delivery')
                    ->label('Completed At')
                    ->dateTime('M j, g:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => $state ? $state . ' ★' : 'No rating')
                    ->color(fn ($state) => match(true) {
                        $state >= 4.5 => 'success',
                        $state >= 4.0 => 'warning',
                        $state > 0 => 'danger',
                        default => 'gray',
                    }),
            ])
            ->heading('Recent Deliveries')
            ->description('Your last 10 completed or cancelled deliveries')
            ->emptyStateHeading('No Recent Deliveries')
            ->emptyStateDescription('You haven\'t completed any deliveries yet.')
            ->emptyStateIcon('heroicon-o-truck');
    }
}
