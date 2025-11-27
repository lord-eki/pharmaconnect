<?php

namespace App\Filament\Insurer\Resources\Insurance\PricingCatalogues;

use App\Filament\Insurer\Resources\Insurance\PricingCatalogues\Pages\CreatePricingCatalogue;
use App\Filament\Insurer\Resources\Insurance\PricingCatalogues\Pages\EditPricingCatalogue;
use App\Filament\Insurer\Resources\Insurance\PricingCatalogues\Pages\ListPricingCatalogues;
use App\Filament\Insurer\Resources\Insurance\PricingCatalogues\Schemas\PricingCatalogueForm;
use App\Filament\Insurer\Resources\Insurance\PricingCatalogues\Tables\PricingCataloguesTable;
use App\Models\Insurance\PricingCatalogue;
use App\Models\Medicine;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PricingCatalogueResource extends Resource
{
    protected static ?string $model = Medicine::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Medicines & Pricing';
    protected static ?string $navigationLabel = 'Pricing Catalogues';

    public static function form(Schema $schema): Schema
    {
        return PricingCatalogueForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PricingCataloguesTable::configure($table);
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
            'index' => ListPricingCatalogues::route('/'),
          
        ];
    }
}
