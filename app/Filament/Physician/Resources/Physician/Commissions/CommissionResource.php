<?php

namespace App\Filament\Physician\Resources\Physician\Commissions;

use App\Filament\Physician\Resources\Physician\Commissions\Pages\CreateCommission;
use App\Filament\Physician\Resources\Physician\Commissions\Pages\EditCommission;
use App\Filament\Physician\Resources\Physician\Commissions\Pages\ListCommissions;
use App\Filament\Physician\Resources\Physician\Commissions\Schemas\CommissionForm;
use App\Filament\Physician\Resources\Physician\Commissions\Tables\CommissionsTable;
use App\Models\Commission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CommissionResource extends Resource
{
    protected static ?string $model = Commission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPercentBadge;

    public static function form(Schema $schema): Schema
    {
        return CommissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommissionsTable::configure($table);
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
            'index' => ListCommissions::route('/'),
            'create' => CreateCommission::route('/create'),
            'edit' => EditCommission::route('/{record}/edit'),
        ];
    }
}
