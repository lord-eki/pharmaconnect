<?php

namespace App\Filament\Operation\Resources\Internals\Riders;

use App\Filament\Operation\Resources\Internals\Riders\Pages\CreateRider;
use App\Filament\Operation\Resources\Internals\Riders\Pages\EditRider;
use App\Filament\Operation\Resources\Internals\Riders\Pages\ListRiders;
use App\Filament\Operation\Resources\Internals\Riders\Schemas\RiderForm;
use App\Filament\Operation\Resources\Internals\Riders\Tables\RidersTable;
use App\Models\Rider;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RiderResource extends Resource
{
    protected static ?string $model = Rider::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    
    public static function form(Schema $schema): Schema
    {
        return RiderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RidersTable::configure($table);
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
            'index' => ListRiders::route('/'),
            'create' => CreateRider::route('/create'),
            'edit' => EditRider::route('/{record}/edit'),
        ];
    }
}
