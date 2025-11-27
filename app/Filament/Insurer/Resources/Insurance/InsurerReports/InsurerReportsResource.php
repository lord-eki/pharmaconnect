<?php

namespace App\Filament\Insurer\Resources\Insurance\InsurerReports;

use App\Filament\Insurer\Resources\Insurance\InsurerReports\Pages\CreateInsurerReports;
use App\Filament\Insurer\Resources\Insurance\InsurerReports\Pages\EditInsurerReports;
use App\Filament\Insurer\Resources\Insurance\InsurerReports\Pages\ListInsurerReports;
use App\Filament\Insurer\Resources\Insurance\InsurerReports\Schemas\InsurerReportsForm;
use App\Filament\Insurer\Resources\Insurance\InsurerReports\Tables\InsurerReportsTable;
use App\Models\InsuranceClaim;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class InsurerReportsResource extends Resource
{
    protected static ?string $model = InsuranceClaim::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'Reports & Statements';

    protected static string|null|UnitEnum $navigationGroup = 'Reports';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('insurance_provider_id', auth()->user()->insuranceProvider->id ?? 0)
            ->with(['prescription.orders', 'patient']);
    }

    public static function form(Schema $schema): Schema
    {
        return InsurerReportsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InsurerReportsTable::configure($table);
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
            'index' => ListInsurerReports::route('/'),
     
        ];
    }
}
