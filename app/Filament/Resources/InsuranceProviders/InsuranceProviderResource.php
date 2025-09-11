<?php

namespace App\Filament\Resources\InsuranceProviders;

use App\Filament\Resources\InsuranceProviders\Pages\CreateInsuranceProvider;
use App\Filament\Resources\InsuranceProviders\Pages\EditInsuranceProvider;
use App\Filament\Resources\InsuranceProviders\Pages\ListInsuranceProviders;
use App\Filament\Resources\InsuranceProviders\Schemas\InsuranceProviderForm;
use App\Filament\Resources\InsuranceProviders\Tables\InsuranceProvidersTable;
use App\Models\InsuranceProvider;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InsuranceProviderResource extends Resource
{
    protected static ?string $model = InsuranceProvider::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string | UnitEnum | null $navigationGroup = 'User Management';

    public static function form(Schema $schema): Schema
    {
        return InsuranceProviderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InsuranceProvidersTable::configure($table);
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
            'index' => ListInsuranceProviders::route('/'),
            'create' => CreateInsuranceProvider::route('/create'),
            'edit' => EditInsuranceProvider::route('/{record}/edit'),
        ];
    }
}
