<?php

namespace App\Filament\Supplier\Resources\Supplier\Inventories;

use App\Filament\Supplier\Resources\Supplier\Inventories\Pages\CreateInventory;
use App\Filament\Supplier\Resources\Supplier\Inventories\Pages\EditInventory;
use App\Filament\Supplier\Resources\Supplier\Inventories\Pages\ListInventories;
use App\Filament\Supplier\Resources\Supplier\Inventories\Schemas\InventoryForm;
use App\Filament\Supplier\Resources\Supplier\Inventories\Tables\InventoriesTable;
use App\Models\SupplierMedicine;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class InventoryResource extends Resource
{
    protected static ?string $model = SupplierMedicine::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?string $navigationLabel = 'Inventory Management';

    protected static ?string $modelLabel = 'Inventory Item';

    public static function getEloquentQuery(): Builder
    {
        $supplier  =  Auth::user()->supplier;

        return parent::getEloquentQuery()
            ->where('supplier_id', $supplier->id)
            ->with(['medicine']);
    }

    public static function form(Schema $schema): Schema
    {
        return InventoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventoriesTable::configure($table);
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
            'index' => ListInventories::route('/'),
            'create' => CreateInventory::route('/create'),
            'edit' => EditInventory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $supplierId = Auth::user()->supplier->id;

        $lowStock = SupplierMedicine::where('supplier_id', $supplierId)
            ->where('stock_quantity', '<=', 10)
            ->where('is_available', true)
            ->count();

        return $lowStock > 0 ? (string) $lowStock : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
