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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    public static function getEloquentQuery(): Builder
    {

        $supplier = Auth::user()->supplier;

        return parent::getEloquentQuery()
            ->where('supplier_id', $supplier->id)->whereIn('status', ['sent_to_supplier', 'pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])
            ->with([
                'quotation.prescription.patient', 'quotation.prescription.physician', 'items',
                'items.medicine',
                'items.medicine.supplierMedicines',
                'quotation',
                'prescription',

            ]);
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
                                TextColumn::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending_review' => 'gray',
                                        'sent_to_supplier' => 'info',
                                        'confirmed' => 'success',
                                        'processing' => 'primary',
                                        'shipped' => 'indigo',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger',
                                        'pending_reassignment' => 'warning',
                                        'needs_manual_assignment' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'pending_review' => 'Pending Review',
                                        'sent_to_supplier' => 'Sent to Supplier',
                                        'confirmed' => 'Confirmed',
                                        'processing' => 'Processing',
                                        'shipped' => 'Shipped',
                                        'delivered' => 'Delivered',
                                        'cancelled' => 'Cancelled',
                                        'pending_reassignment' => 'Pending Reassignment',
                                        'needs_manual_assignment' => 'Needs Manual Assignment',
                                        default => ucfirst(str_replace('_', ' ', $state)),
                                    }),

                                TextEntry::make('supplier_total')
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

                Section::make('Order Items')
                    ->schema([
                        RepeatableEntry::make('items')
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

                                        TextEntry::make('supplier_price')
                                            ->label('Unit Price')
                                            ->money('KES'),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('total_price')
                                            ->label('Total Price')
                                            ->money('KES')
                                            ->size('lg')
                                            ->weight('bold')
                                            ->formatStateUsing(fn (string $state, $record): string => 'KES '.$record->supplier_price * $record->quantity),

                                    ]),
                            ])
                            ->columns(1)
                            ->contained(false),
                    ]),

            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $supplier = Auth::user()->supplier;

        if(!$supplier) {
            return null;
        }


        $pending = Order::where('supplier_id', $supplier->id)
            ->whereIn('status', ['sent_to_supplier','pending'])
            ->count();


        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
