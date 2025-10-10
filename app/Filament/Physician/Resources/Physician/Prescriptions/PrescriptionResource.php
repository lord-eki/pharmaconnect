<?php

namespace App\Filament\Physician\Resources\Physician\Prescriptions;

use App\Filament\Physician\Resources\Physician\Prescriptions\Pages\CreatePrescription;
use App\Filament\Physician\Resources\Physician\Prescriptions\Pages\EditPrescription;
use App\Filament\Physician\Resources\Physician\Prescriptions\Pages\ListPrescriptions;
use App\Filament\Physician\Resources\Physician\Prescriptions\Pages\ViewPrescription;
use App\Filament\Physician\Resources\Physician\Prescriptions\Schemas\PrescriptionForm;
use App\Filament\Physician\Resources\Physician\Prescriptions\Tables\PrescriptionsTable;
use App\Models\Prescription;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PrescriptionResource extends Resource
{
    protected static ?string $model = Prescription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function form(Schema $schema): Schema
    {
        return PrescriptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrescriptionsTable::configure($table);
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
            'index' => ListPrescriptions::route('/'),
            'create' => CreatePrescription::route('/create'),
            'view' => ViewPrescription::route('/{record}'),
            'edit' => EditPrescription::route('/{record}/edit'),
        ];
    }
}
