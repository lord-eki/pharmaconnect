<?php

namespace App\Filament\Operation\Resources\Accountoverviews;

use App\Filament\Operation\Resources\Accountoverviews\Pages\ListAccountoverviews;
use App\Filament\Operation\Resources\Accountoverviews\Schemas\AccountoverviewForm;
use App\Filament\Operation\Resources\Accountoverviews\Tables\AccountoverviewsTable;
use App\Models\Receivable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AccountoverviewResource extends Resource
{
    protected static ?string $model = Receivable::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Account Overview';

    protected static string|UnitEnum|null $navigationGroup = 'Finance & Payments';

    public static function form(Schema $schema): Schema
    {
        return AccountoverviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccountoverviewsTable::configure($table);
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
            'index' => ListAccountoverviews::route('/'),

        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
