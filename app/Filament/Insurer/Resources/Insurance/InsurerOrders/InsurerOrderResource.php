<?php

namespace App\Filament\Insurer\Resources\Insurance\InsurerOrders;

use App\Filament\Insurer\Resources\Insurance\InsurerOrders\Pages\CreateInsurerOrder;
use App\Filament\Insurer\Resources\Insurance\InsurerOrders\Pages\EditInsurerOrder;
use App\Filament\Insurer\Resources\Insurance\InsurerOrders\Pages\ListInsurerOrders;
use App\Filament\Insurer\Resources\Insurance\InsurerOrders\Pages\ViewInsurerOrder;
use App\Filament\Insurer\Resources\Insurance\InsurerOrders\Schemas\InsurerOrderForm;
use App\Filament\Insurer\Resources\Insurance\InsurerOrders\Tables\InsurerOrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class InsurerOrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $navigationLabel = 'Order Prescriptions';

    protected static string|UnitEnum|null $navigationGroup = 'Claims Management';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('prescription.insuranceClaim', function ($query) {
                $query->where('insurance_provider_id', auth()->user()->insuranceProvider->id ?? 0);
            })
            ->with(['prescription.patient', 'prescription.insuranceClaim', 'supplier', 'items.medicine']);
    }

    public static function form(Schema $schema): Schema
    {
        return InsurerOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InsurerOrdersTable::configure($table);
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
            'index' => ListInsurerOrders::route('/'),
            'create' => CreateInsurerOrder::route('/create'),
            'view' => ViewInsurerOrder::route('/{record}'),
            'edit' => EditInsurerOrder::route('/{record}/edit'),
        ];
    }

    public static function canEdit($record): bool
    {
        return in_array($record->status, ['pending', 'confirmed']);
    }
}
