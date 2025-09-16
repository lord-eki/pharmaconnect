<?php

namespace App\Filament\Resources\MedicineInteractions;

use App\Filament\Resources\MedicineInteractions\Pages\ManageMedicineInteractions;
use App\Models\MedicineInteraction;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MedicineInteractionResource extends Resource
{
    protected static ?string $model = MedicineInteraction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCursorArrowRipple;

    protected static string|UnitEnum|null $navigationGroup = 'Medicine Management';


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('medicine_id')
                    ->relationship('medicine', 'id')
                    ->required(),
                Select::make('interacting_medicine_id')
                    ->relationship('interactingMedicine', 'id')
                    ->required(),
                Select::make('interaction_type')
                    ->options(['minor' => 'Minor', 'moderate' => 'Moderate', 'major' => 'Major'])
                    ->required(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('clinical_significance')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('medicine.id')
                    ->searchable(),
                TextColumn::make('interactingMedicine.id')
                    ->searchable(),
                TextColumn::make('interaction_type'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMedicineInteractions::route('/'),
        ];
    }
}
