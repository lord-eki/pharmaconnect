<?php

namespace App\Filament\Insurer\Resources\ExternalOrders;

use App\Filament\Insurer\Resources\ExternalOrders\Pages\CreateExternalOrder;
use App\Filament\Insurer\Resources\ExternalOrders\Pages\EditExternalOrder;
use App\Filament\Insurer\Resources\ExternalOrders\Pages\ListExternalOrders;
use App\Filament\Insurer\Resources\ExternalOrders\Pages\ViewExternalOrder;
use App\Filament\Insurer\Resources\ExternalOrders\Schemas\ExternalOrderForm;
use App\Filament\Insurer\Resources\ExternalOrders\Tables\ExternalOrdersTable;
use App\Models\ExternalOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ExternalOrderResource extends Resource
{
    protected static ?string $model = ExternalOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsPointingIn;

    protected static string|UnitEnum|null $navigationGroup = 'Claim Management';

    protected static ?string $modelLabel = 'Orders';

    public static function form(Schema $schema): Schema
    {
        return ExternalOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExternalOrdersTable::configure($table);
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
            'index' => ListExternalOrders::route('/'),
            'create' => CreateExternalOrder::route('/create'),
            'edit' => EditExternalOrder::route('/{record}/edit'),
            'view' => ViewExternalOrder::route('/{record}'),
        ];
    }


    //   public static function getEloquentQuery(): Builder
    // {
    //     $query = parent::getEloquentQuery();

    //     if (auth()->user()->insuranceProvider) {
    //         $query->where('insurance_provider_id', auth()->user()->insuranceProvider->id);
    //     }

    //     return $query;
    // }

    
}
