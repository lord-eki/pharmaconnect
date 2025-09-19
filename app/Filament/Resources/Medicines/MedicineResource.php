<?php

namespace App\Filament\Resources\Medicines;

use App\Filament\Imports\MedicineImporter;
use App\Filament\Resources\Medicines\Pages\ManageMedicines;
use App\Models\Medicine;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MedicineResource extends Resource
{
    protected static ?string $model = Medicine::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static string|UnitEnum|null $navigationGroup = 'Medicine Management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    Select::make('category_id')
                        ->relationship('category', 'name')
                        ->required(),
                    TextInput::make('generic_name')
                        ->required(),
                    TextInput::make('brand_name')
                        ->default(null),
                    TextInput::make('strength')
                        ->required(),
                    TextInput::make('dosage_form')
                        ->required(),
                    TextInput::make('pack_size')
                        ->required(),
                    TextInput::make('manufacturer')
                        ->required(),
                    Textarea::make('active_ingredients')
                        ->required(),
                    Textarea::make('description')
                        ->default(null),
                    Textarea::make('usage_instructions')
                        ->default(null),
                    Textarea::make('side_effects')
                        ->default(null),
                    Textarea::make('contraindications')
                        ->default(null),
                    Textarea::make('storage_requirements')
                        ->default(null),
                    Toggle::make('prescription_required')
                        ->required(),
                    Toggle::make('controlled_substance')
                        ->required(),
                    TextInput::make('ppb_registration_number')
                        ->default(null),
                    Toggle::make('is_active')
                        ->required(),
                ])->columns(2)->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->date()->label('Date')
                    ->sortable(),
                TextColumn::make('category.name')
                    ->searchable(),
                TextColumn::make('generic_name')
                    ->searchable(),
                TextColumn::make('brand_name')
                    ->searchable(),
                TextColumn::make('strength')
                    ->searchable(),
                TextColumn::make('dosage_form')
                    ->searchable(),
                TextColumn::make('pack_size')
                    ->searchable(),
                TextColumn::make('manufacturer')
                    ->searchable(),
                IconColumn::make('prescription_required')
                    ->boolean(),
                IconColumn::make('controlled_substance')
                    ->boolean(),
                TextColumn::make('ppb_registration_number')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->headerActions([
                ImportAction::make('Import Medicine Catalog')->icon(Heroicon::OutlinedArrowDownTray)->importer(MedicineImporter::class),
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
            'index' => ManageMedicines::route('/'),
        ];
    }
}
