<?php

namespace App\Filament\Resources\MarkupRules;

use App\Filament\Resources\MarkupRules\Pages\CreateMarkupRule;
use App\Filament\Resources\MarkupRules\Pages\EditMarkupRule;
use App\Filament\Resources\MarkupRules\Pages\ListMarkupRules;
use App\Filament\Resources\MarkupRules\Schemas\MarkupRuleForm;
use App\Filament\Resources\MarkupRules\Tables\MarkupRulesTable;
use App\Models\MarkupRule;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MarkupRuleResource extends Resource
{
    protected static ?string $model = MarkupRule::class;

        protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;
    protected static string|UnitEnum|null $navigationGroup = 'Settings';


    public static function form(Schema $schema): Schema
    {
        return MarkupRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarkupRulesTable::configure($table);
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
            'index' => ListMarkupRules::route('/'),
            'create' => CreateMarkupRule::route('/create'),
            'edit' => EditMarkupRule::route('/{record}/edit'),
        ];
    }
}
