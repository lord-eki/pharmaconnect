<?php

namespace App\Filament\Physician\Resources\Physician\ClaimForms;

use App\Filament\Physician\Resources\Physician\ClaimForms\Pages\CreateClaimForm;
use App\Filament\Physician\Resources\Physician\ClaimForms\Pages\EditClaimForm;
use App\Filament\Physician\Resources\Physician\ClaimForms\Pages\ListClaimForms;
use App\Filament\Physician\Resources\Physician\ClaimForms\Pages\ViewClaimForm;
use App\Filament\Physician\Resources\Physician\ClaimForms\Schemas\ClaimFormForm;
use App\Filament\Physician\Resources\Physician\ClaimForms\Schemas\ClaimFormInfolist;
use App\Filament\Physician\Resources\Physician\ClaimForms\Tables\ClaimFormsTable;
use App\Models\ClaimForm;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClaimFormResource extends Resource
{
    protected static ?string $model = ClaimForm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    public static function form(Schema $schema): Schema
    {
        return ClaimFormForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClaimFormInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClaimFormsTable::configure($table);
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
            'index' => ListClaimForms::route('/'),
            'create' => CreateClaimForm::route('/create'),
            'view' => ViewClaimForm::route('/{record}'),
            'edit' => EditClaimForm::route('/{record}/edit'),
        ];
    }
}
