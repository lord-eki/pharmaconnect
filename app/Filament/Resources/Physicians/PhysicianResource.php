<?php

namespace App\Filament\Resources\Physicians;

use App\Filament\Resources\Physicians\Pages\CreatePhysician;
use App\Filament\Resources\Physicians\Pages\EditPhysician;
use App\Filament\Resources\Physicians\Pages\ListPhysicians;
use App\Filament\Resources\Physicians\Schemas\PhysicianForm;
use App\Filament\Resources\Physicians\Tables\PhysiciansTable;
use App\Models\Physician;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PhysicianResource extends Resource
{
    protected static ?string $model = Physician::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static string|UnitEnum|null $navigationGroup = 'User Management';


    public static function form(Schema $schema): Schema
    {
        return PhysicianForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PhysiciansTable::configure($table);
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
            'index' => ListPhysicians::route('/'),
            'create' => CreatePhysician::route('/create'),
            'edit' => EditPhysician::route('/{record}/edit'),
        ];
    }
}
