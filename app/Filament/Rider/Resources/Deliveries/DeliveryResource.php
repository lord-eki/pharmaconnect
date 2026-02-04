<?php

namespace App\Filament\Rider\Resources\Deliveries;

use App\Filament\Rider\Resources\Deliveries\Pages\CreateDelivery;
use App\Filament\Rider\Resources\Deliveries\Pages\EditDelivery;
use App\Filament\Rider\Resources\Deliveries\Pages\ListDeliveries;
use App\Filament\Rider\Resources\Deliveries\Pages\ViewDelivery;
use App\Filament\Rider\Resources\Deliveries\Schemas\DeliveryForm;
use App\Filament\Rider\Resources\Deliveries\Schemas\DeliveryInfolist;
use App\Filament\Rider\Resources\Deliveries\Tables\DeliveriesTable;
use App\Models\Delivery;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DeliveryResource extends Resource
{
    protected static ?string $model = Delivery::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    public static function form(Schema $schema): Schema
    {
        return DeliveryForm::configure($schema);
    }


    public static function table(Table $table): Table
    {
        return DeliveriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('rider_id', auth()->user()->rider->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveries::route('/'),
            'create' => CreateDelivery::route('/create'),
            // 'view' => ViewDelivery::route('/{record}'),
            // 'edit' => EditDelivery::route('/{record}/edit'),
        ];
    }

}
