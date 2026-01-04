<?php

namespace App\Filament\Operation\Resources\Patients;

use App\Filament\Operation\Resources\Patients\Pages\CreatePatient;
use App\Filament\Operation\Resources\Patients\Pages\EditPatient;
use App\Filament\Operation\Resources\Patients\Pages\ListPatients;
use App\Filament\Operation\Resources\Patients\Schemas\PatientForm;
use App\Filament\Operation\Resources\Patients\Tables\PatientsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\Patient;
use UnitEnum;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Logistics';

    public static function form(Schema $schema): Schema
    {
        return PatientForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PatientsTable::configure($table);
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
            'index' => ListPatients::route('/'),
            // 'create' => CreatePatient::route('/create'),
            // 'edit' => EditPatient::route('/{record}/edit'),
        ];
    }
}
