<?php

namespace App\Filament\Physician\Resources\Physician\Orders;

use App\Filament\Physician\Resources\Physician\Orders\Pages\CreateOrder;
use App\Filament\Physician\Resources\Physician\Orders\Pages\EditOrder;
use App\Filament\Physician\Resources\Physician\Orders\Pages\ListOrders;
use App\Filament\Physician\Resources\Physician\Orders\Pages\ViewOrder;
use App\Filament\Physician\Resources\Physician\Orders\Schemas\OrderForm;
use App\Filament\Physician\Resources\Physician\Orders\Schemas\OrderInfolist;
use App\Filament\Physician\Resources\Physician\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
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
            
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereHas('prescription', fn ($q) => 
            $q->where('physician_id', Auth::id())
        )
        ->whereIn('status', ['pending', 'confirmed', 'processing', 'shipped'])
        ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
