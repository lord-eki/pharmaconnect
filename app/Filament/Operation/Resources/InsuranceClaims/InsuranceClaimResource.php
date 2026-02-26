<?php

namespace App\Filament\Operation\Resources\InsuranceClaims;

use App\Filament\Operation\Resources\InsuranceClaims\Pages\CreateInsuranceClaim;
use App\Filament\Operation\Resources\InsuranceClaims\Pages\EditInsuranceClaim;
use App\Filament\Operation\Resources\InsuranceClaims\Pages\ListInsuranceClaims;
use App\Filament\Operation\Resources\InsuranceClaims\Schemas\InsuranceClaimForm;
use App\Filament\Operation\Resources\InsuranceClaims\Tables\InsuranceClaimsTable;
use App\Models\InsuranceClaim;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class InsuranceClaimResource extends Resource
{
    protected static ?string $model = InsuranceClaim::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Finance & Payments';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'patient',
            'prescription.items.medicine',
            'prescription.orders',
            'externalOrder',
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return InsuranceClaimForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InsuranceClaimsTable::configure($table);
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
            'index' => ListInsuranceClaims::route('/'),
            // 'create' => CreateInsuranceClaim::route('/create'),
            // 'edit' => EditInsuranceClaim::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'submitted')
            ->count();

        return $count > 0 ? (string) $count : null;
    }
}
