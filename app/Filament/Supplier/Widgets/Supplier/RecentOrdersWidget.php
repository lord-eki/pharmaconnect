<?php

namespace App\Filament\Supplier\Widgets\Supplier;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class RecentOrdersWidget extends TableWidget
{

        protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 1;

    public function table(Table $table): Table
    {
        $supplierId = Auth::user()->userProfile->id ?? null;

        return $table
            ->query(
                Order::where('supplier_id', $supplierId)
                    ->whereIn('status', ['pending', 'confirmed', 'processing', 'shipped'])
                    ->latest('ordered_at')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('prescription.patient.first_name')
                    ->label('Patient')
                    ->formatStateUsing(fn ($record) => $record->prescription->patient
                            ? "{$record->prescription->patient->first_name} {$record->prescription->patient->last_name}"
                            : 'N/A'
                    )
                    ->wrap(),

                TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('KES'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'processing' => 'primary',
                        'shipped' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('expected_delivery')
                    ->label('Expected Delivery')
                    ->date()
                    ->color(fn ($state, $record) => $state && $state->isPast() && $record->status !== 'delivered'
                            ? 'danger'
                            : 'success'
                    ),

                TextColumn::make('ordered_at')
                    ->label('Ordered')
                    ->since(),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye'),
                // ->url(fn (Order $record): string => route('filament.supplier.resources.orders.view', ['record' => $record])),
            ]);
    }

    public function getHeading(): string
    {
        return 'Recent Orders';
    }
}
