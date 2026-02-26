<?php

namespace App\Filament\Resources\Auditlogs;

use App\Filament\Resources\Auditlogs\Pages\CreateAuditlog;
use App\Filament\Resources\Auditlogs\Pages\EditAuditlog;
use App\Filament\Resources\Auditlogs\Pages\ListAuditlogs;
use App\Filament\Resources\Auditlogs\Schemas\AuditlogForm;
use App\Filament\Resources\Auditlogs\Tables\AuditlogsTable;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AuditlogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationLabel = 'Audit Logs';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    public static function form(Schema $schema): Schema
    {
        return AuditlogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AuditlogsTable::configure($table);
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
            'index' => ListAuditlogs::route('/'),
            // 'create' => CreateAuditlog::route('/create'),
            // 'edit' => EditAuditlog::route('/{record}/edit'),
        ];
    }

 
}
