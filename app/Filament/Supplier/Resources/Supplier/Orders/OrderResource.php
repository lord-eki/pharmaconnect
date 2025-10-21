<?php

namespace App\Filament\Supplier\Resources\Supplier\Orders;

use App\Filament\Supplier\Resources\Supplier\Orders\Pages\EditOrder;
use App\Filament\Supplier\Resources\Supplier\Orders\Pages\ListOrders;
use App\Filament\Supplier\Resources\Supplier\Orders\Pages\ViewOrder;
use App\Filament\Supplier\Resources\Supplier\Orders\Schemas\OrderForm;
use App\Filament\Supplier\Resources\Supplier\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    public static function getEloquentQuery(): Builder
    {
        $supplierId = Auth::user()->userProfile->id ?? null;

        return parent::getEloquentQuery()
            ->where('supplier_id', $supplierId)
            ->with(['quotation.prescription.patient', 'quotation.prescription.physician', 'orderItems']);
    }

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Overview')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('order_number')
                                    ->label('Order Number (LPO)')
                                    ->badge()
                                    ->color('primary')
                                    ->copyable(),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'processing' => 'primary',
                                        'shipped' => 'success',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }),

                                TextEntry::make('total_amount')
                                    ->label('Total Amount')
                                    ->money('KES')
                                    ->size('lg')
                                    ->weight('bold'),

                                TextEntry::make('ordered_at')
                                    ->label('Order Date')
                                    ->dateTime()
                                    ->badge(),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('expected_delivery')
                                    ->label('Expected Delivery')
                                    ->dateTime()
                                    ->color(fn ($state, $record) => $state && $state->isPast() && $record->status !== 'delivered'
                                            ? 'danger'
                                            : 'success'
                                    ),

                                TextEntry::make('delivered_at')
                                    ->label('Delivered At')
                                    ->dateTime()
                                    ->placeholder('Not delivered yet'),

                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->since(),
                            ]),
                    ]),

                Section::make('Customer Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('prescription.prescription_number')
                                    ->label('Prescription Number')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('prescription.physician.name')
                                    ->label('Prescribing Physician')
                                    ->icon('heroicon-o-user'),

                                TextEntry::make('prescription.patient.first_name')
                                    ->label('Patient')
                                    ->formatStateUsing(fn ($record) => $record->prescription->patient
                                            ? "{$record->prescription->patient->first_name} {$record->prescription->patient->last_name}"
                                            : 'N/A'
                                    )
                                    ->icon('heroicon-o-identification'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('prescription.patient.phone')
                                    ->label('Contact Phone')
                                    ->icon('heroicon-o-phone'),

                                TextEntry::make('prescription.patient.address')
                                    ->label('Delivery Address')
                                    ->icon('heroicon-o-map-pin')
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Order Items')
                    ->schema([
                        RepeatableEntry::make('orderItems')
                            ->label('')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('medicine.generic_name')
                                            ->label('Medicine')
                                            ->formatStateUsing(fn ($record) => $record->medicine
                                                    ? "{$record->medicine->generic_name} - {$record->medicine->brand_name} ({$record->medicine->strength})"
                                                    : 'N/A'
                                            )
                                            ->columnSpan(2),

                                        TextEntry::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->badge(),

                                        TextEntry::make('unit_price')
                                            ->label('Unit Price')
                                            ->money('KES'),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('total_price')
                                            ->label('Total Price')
                                            ->money('KES')
                                            ->size('lg')
                                            ->weight('bold'),

                                        TextEntry::make('status')
                                            ->label('Item Status')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'pending' => 'warning',
                                                'confirmed' => 'info',
                                                'shipped' => 'success',
                                                'delivered' => 'success',
                                                default => 'gray',
                                            }),
                                    ]),
                            ])
                            ->columns(1)
                            ->contained(false),
                    ]),

                Section::make('Additional Information')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Order Notes')
                            ->placeholder('No notes')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $supplierId = Auth::user()->userProfile->id ?? null;

        $pending = Order::where('supplier_id', $supplierId)
            ->where('status', 'pending')
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
