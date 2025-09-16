<?php

namespace App\Filament\Resources\PrescriptionItems;

use App\Filament\Resources\PrescriptionItems\Pages\ManagePrescriptionItems;
use App\Models\PrescriptionItem;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PrescriptionItemResource extends Resource
{
    protected static ?string $model = PrescriptionItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static string|UnitEnum|null $navigationGroup = 'Medicine Management';


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('prescription_id')
                    ->relationship('prescription', 'id')
                    ->required(),
                Select::make('medicine_id')
                    ->relationship('medicine', 'id')
                    ->required(),
                TextInput::make('quantity')
                    ->required()
                    ->numeric(),
                Textarea::make('dosage_instructions')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('duration_days')
                    ->numeric()
                    ->default(null),
                TextInput::make('frequency')
                    ->default(null),
                TextInput::make('unit_price')
                    ->numeric()
                    ->default(null),
                TextInput::make('total_price')
                    ->numeric()
                    ->default(null),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'quoted' => 'Quoted',
                        'ordered' => 'Ordered',
                        'fulfilled' => 'Fulfilled',
                    ])
                    ->default('pending')
                    ->required(),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('prescription.id')
                    ->searchable(),
                TextColumn::make('medicine.id')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('duration_days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('frequency')
                    ->searchable(),
                TextColumn::make('unit_price')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_price')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status'),
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
            'index' => ManagePrescriptionItems::route('/'),
        ];
    }
}
