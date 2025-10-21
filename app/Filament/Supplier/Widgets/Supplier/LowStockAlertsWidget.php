<?php

namespace App\Filament\Supplier\Widgets\Supplier;

use App\Models\SupplierMedicine;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class LowStockAlertsWidget extends TableWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        $supplierId = Auth::user()->userProfile->id ?? null;

        return $table
            ->query(
                SupplierMedicine::where('supplier_id', $supplierId)
                    ->where('is_available', true)
                    ->where('stock_quantity', '<=', 10)
                    ->orderBy('stock_quantity', 'asc')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('medicine.generic_name')
                    ->label('Medicine')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('medicine.brand_name')
                    ->label('Brand')
                    ->searchable(),

                TextColumn::make('stock_quantity')
                    ->label('Current Stock')
                    ->numeric()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state <= 5 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'success',
                    }),

                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('KES'),

                TextColumn::make('expiry_date')
                    ->label('Expiry Date')
                    ->date()
                    ->color(fn ($state) => $state && $state->lt(now()->addMonths(3)) ? 'danger' : 'success'),

                TextColumn::make('last_updated')
                    ->label('Last Updated')
                    ->since(),
            ])
            ->actions([
                Action::make('update_stock')
                    ->label('Update')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('stock_quantity')
                            ->label('New Stock Quantity')
                            ->required()
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->action(function (SupplierMedicine $record, array $data) {
                        $record->update([
                            'stock_quantity' => $data['stock_quantity'],
                            'last_updated' => now(),
                        ]);
                    })
                    ->successNotificationTitle('Stock updated successfully'),
            ]);
    }

    public function getHeading(): string
    {
        return 'Low Stock Alerts';
    }
}
