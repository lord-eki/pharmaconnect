<?php

namespace App\Filament\Resources\InsuranceClaims;

use App\Filament\Resources\InsuranceClaims\Pages\CreateInsuranceClaim;
use App\Filament\Resources\InsuranceClaims\Pages\EditInsuranceClaim;
use App\Filament\Resources\InsuranceClaims\Pages\ListInsuranceClaims;
use App\Filament\Resources\InsuranceClaims\Pages\ViewInsuranceClaim;
use App\Filament\Resources\InsuranceClaims\Schemas\InsuranceClaimForm;
use App\Filament\Resources\InsuranceClaims\Schemas\InsuranceClaimInfolist;
use App\Filament\Resources\InsuranceClaims\Tables\InsuranceClaimsTable;
use App\Models\InsuranceClaim;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InsuranceClaimResource extends Resource
{
    protected static ?string $model = InsuranceClaim::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return InsuranceClaimForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InsuranceClaimInfolist::configure($schema);
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
            'create' => CreateInsuranceClaim::route('/create'),
            'view' => ViewInsuranceClaim::route('/{record}'),
            'edit' => EditInsuranceClaim::route('/{record}/edit'),
        ];
    }

        public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'submitted')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
