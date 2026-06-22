<?php

namespace App\Filament\Supplier\Resources\Supplier\Inventories\Pages;

use App\Filament\Supplier\Resources\Supplier\Inventories\InventoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListInventories extends ListRecords
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-folder-plus')->outlined()->label('Add Inventory Item'),
        ];
    }

     public function getTabs(): array
    {
        $supplier =  Auth::user()->supplier;


        return [
            'all' => Tab::make('All Products')
                ->badge(fn () => \App\Models\SupplierMedicine::where('supplier_id', $supplier->id)->count()),

            'available' => Tab::make('Available')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_available', true))
                ->badge(fn () => \App\Models\SupplierMedicine::where('supplier_id', $supplier->id)
                    ->where('is_available', true)->count())
                ->badgeColor('success'),

            'low_stock' => Tab::make('Low Stock')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('stock_quantity', '<=', 10)
                    ->where('stock_quantity', '>', 0)
                )
                ->badge(fn () => \App\Models\SupplierMedicine::where('supplier_id', $supplier->id)
                    ->where('stock_quantity', '<=', 10)
                    ->where('stock_quantity', '>', 0)
                    ->count())
                ->badgeColor('warning'),

            'out_of_stock' => Tab::make('Out of Stock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('stock_quantity', 0))
                ->badge(fn () => \App\Models\SupplierMedicine::where('supplier_id', $supplier->id)
                    ->where('stock_quantity', 0)->count())
                ->badgeColor('danger'),

            'expiring' => Tab::make('Expiring Soon')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereNotNull('expiry_date')
                    ->where('expiry_date', '<=', now()->addMonths(3))
                )
                ->badge(fn () => \App\Models\SupplierMedicine::where('supplier_id', $supplier->id)
                    ->whereNotNull('expiry_date')
                    ->where('expiry_date', '<=', now()->addMonths(3))
                    ->count())
                ->badgeColor('danger'),
        ];
    
}
}
