<?php

namespace App\Filament\Operation\Resources\Internals\Receivables;

use App\Filament\Operation\Resources\Internals\Receivables\Pages\CreateReceivables;
use App\Filament\Operation\Resources\Internals\Receivables\Pages\EditReceivables;
use App\Filament\Operation\Resources\Internals\Receivables\Pages\ListReceivables;
use App\Filament\Operation\Resources\Internals\Receivables\Schemas\ReceivablesForm;
use App\Filament\Operation\Resources\Internals\Receivables\Tables\ReceivablesTable;
use App\Models\Receivable;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReceivablesResource extends Resource
{
    protected static ?string $model = Receivable::class;

    protected static ?string $modelLabel = 'Receivables';



    protected static string | UnitEnum | null $navigationGroup = 'Finance & Payments';



    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownOnSquareStack;

    public static function form(Schema $schema): Schema
    {
        return ReceivablesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReceivablesTable::configure($table);
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
            'index' => ListReceivables::route('/'),
            'create' => CreateReceivables::route('/create'),
            // 'edit' => EditReceivables::route('/{record}/edit'),
        ];
    }
}
