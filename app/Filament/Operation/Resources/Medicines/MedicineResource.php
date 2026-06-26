<?php

namespace App\Filament\Operation\Resources\Medicines;

use App\Filament\Operation\Resources\Medicines\Pages\CreateMedicine;
use App\Filament\Operation\Resources\Medicines\Pages\EditMedicine;
use App\Filament\Operation\Resources\Medicines\Pages\ListMedicines;
use App\Filament\Operation\Resources\Medicines\Schemas\MedicineForm;
use App\Filament\Operation\Resources\Medicines\Tables\MedicinesTable;
use App\Models\Insurance\PricingCatalogue;
use App\Models\Medicine;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MedicineResource extends Resource
{
    protected static ?string $model = Medicine::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    public static function form(Schema $schema): Schema
    {
        return MedicineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MedicinesTable::configure($table);
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
            'index' => ListMedicines::route('/'),
            // 'create' => CreateMedicine::route('/create'),
            // 'edit' => EditMedicine::route('/{record}/edit'),
        ];
    }
}
