<?php

namespace App\Filament\Supplier\Resources\Supplier\Inventories\Tables;

use App\Filament\Exports\SupplierMedicineExporter;
use App\Filament\Imports\SupplierMedicineImporter;
use App\Models\SupplierMedicine;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('medicine.generic_name')
                    ->label('Generic Name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('medicine.brand_name')
                    ->label('Brand Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('medicine.strength')
                    ->label('Strength')
                    ->searchable(),

                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('KES')
                    ->sortable(),

                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state <= 10 => 'warning',
                        $state <= 50 => 'info',
                        default => 'success',
                    }),

                IconColumn::make('is_available')
                    ->label('Available')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('expiry_date')
                    ->label('Expiry Date')
                    ->date()
                    ->sortable()
                    ->color(fn ($state) => $state && $state->lt(now()->addMonths(3)) ? 'danger' : 'success'),

                TextColumn::make('last_updated')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('is_available')
                    ->label('Availability')
                    ->options([
                        '1' => 'Available',
                        '0' => 'Unavailable',
                    ]),

                Filter::make('low_stock')
                    ->label('Low Stock (≤10)')
                    ->query(fn (Builder $query) => $query->where('stock_quantity', '<=', 10)),

                Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query) => $query->where('stock_quantity', 0)),

                Filter::make('expiring_soon')
                    ->label('Expiring Soon (3 months)')
                    ->query(fn (Builder $query) => $query
                        ->whereNotNull('expiry_date')
                        ->where('expiry_date', '<=', now()->addMonths(3))
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),

                    Action::make('quick_update_stock')
                        ->label('Update Stock')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->form([
                            TextInput::make('stock_quantity')
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

                    Action::make('quick_update_price')
                        ->label('Update Price')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('warning')
                        ->form([
                            TextInput::make('unit_price')
                                ->label('New Unit Price (KES)')
                                ->required()
                                ->numeric()
                                ->prefix('KES')
                                ->minValue(0)
                                ->step(0.01),
                        ])
                        ->action(function (SupplierMedicine $record, array $data) {
                            $record->update([
                                'unit_price' => $data['unit_price'],
                                'last_updated' => now(),
                            ]);
                        })
                        ->successNotificationTitle('Price updated successfully'),

                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([

                    ExportBulkAction::make()
                        ->exporter(SupplierMedicineExporter::class)
                        ->icon('heroicon-o-arrow-down-tray')
                        ->label('Export Selected')
                        ->color('primary')
                        ->fileName(fn (): string => 'selected-medicines-' . now()->format('Y-m-d-His')),

                    BulkAction::make('mark_available')
                        ->label('Mark as Available')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_available' => true]))
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Items marked as available'),

                    BulkAction::make('mark_unavailable')
                        ->label('Mark as Unavailable')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_available' => false]))
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Items marked as unavailable'),

                    DeleteBulkAction::make(),
                ]),
            ])->headerActions([
                ImportAction::make()->importer(SupplierMedicineImporter::class)->icon('heroicon-o-arrow-up-tray')
                    ->label('Import Medicines')
                    ->color('gray')
                    ->csvDelimiter(',')
                    ->maxRows(10000),
            ]);
    }
}
