<?php

namespace App\Filament\Supplier\Resources\Supplier\OrderReports;

use App\Filament\Supplier\Resources\Supplier\OrderReports\Pages\CreateOrderReports;
use App\Filament\Supplier\Resources\Supplier\OrderReports\Pages\EditOrderReports;
use App\Filament\Supplier\Resources\Supplier\OrderReports\Pages\ListOrderReports;
use App\Filament\Supplier\Resources\Supplier\OrderReports\Schemas\OrderReportsForm;
use App\Filament\Supplier\Resources\Supplier\OrderReports\Tables\OrderReportsTable;
use App\Models\Order;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OrderReportsResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Order Reports';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    public static function getEloquentQuery(): Builder
    {
        $supplier = Auth::user()->supplier;

        return parent::getEloquentQuery()
            ->where('supplier_id', $supplier->id)
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->with(['quotation.prescription.patient', 'quotation.prescription.physician', 'items']);
    }

    public static function form(Schema $schema): Schema
    {
        return OrderReportsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderReportsTable::configure($table);
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
            'index' => ListOrderReports::route('/'),
        ];
    }
}
