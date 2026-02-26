<?php

namespace App\Filament\Operation\Resources\Internals\Payables;

use App\Filament\Operation\Resources\Internals\Payables\Pages\CreatePayables;
use App\Filament\Operation\Resources\Internals\Payables\Pages\EditPayables;
use App\Filament\Operation\Resources\Internals\Payables\Pages\ListPayables;
use App\Filament\Operation\Resources\Internals\Payables\Schemas\PayablesForm;
use App\Filament\Operation\Resources\Internals\Payables\Tables\PayablesTable;
use App\Models\Payable;
use App\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PayablesResource extends Resource
{
    protected static ?string $model = Payable::class;

    protected static ?string $modelLabel = 'Payables';

    protected static string|UnitEnum|null $navigationGroup = 'Finance & Payments';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowTrendingUp;

    public static function form(Schema $schema): Schema
    {
        return PayablesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayablesTable::configure($table);
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
            'index' => ListPayables::route('/'),
            'create' => CreatePayables::route('/create'),
            'edit' => EditPayables::route('/{record}/edit'),
        ];
    }
}
