<?php

namespace App\Filament\Physician\Resources\Physician\Patients;

use App\Filament\Physician\Resources\Physician\Patients\Pages\CreatePatient;
use App\Filament\Physician\Resources\Physician\Patients\Pages\EditPatient;
use App\Filament\Physician\Resources\Physician\Patients\Pages\ListPatients;
use App\Filament\Physician\Resources\Physician\Patients\Schemas\PatientForm;
use App\Filament\Physician\Resources\Physician\Patients\Tables\PatientsTable;
use App\Models\Patient;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

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
            'create' => CreatePatient::route('/create'),
            'edit' => EditPatient::route('/{record}/edit'),
        ];
    }
}
