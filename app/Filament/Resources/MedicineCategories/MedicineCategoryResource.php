<?php

namespace App\Filament\Resources\MedicineCategories;

use App\Filament\Resources\MedicineCategories\Pages\ManageMedicineCategories;
use App\Models\MedicineCategory;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

class MedicineCategoryResource extends Resource
{
    protected static ?string $model = MedicineCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Medicine Management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([

                    TextInput::make('name')
                        ->required(),
                    Textarea::make('description')
                        ->default(null)
                        ->columnSpanFull(),
                    Select::make('parent_id')
                        ->relationship('parent', 'name')
                        ->default(null),
                    Toggle::make('is_active')
                        ->required(),
                    TextInput::make('sort_order')
                        ->required()
                        ->numeric()
                        ->default(0),
                ])->columns(2)->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()->label('Date'),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('description')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),

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
            'index' => ManageMedicineCategories::route('/'),
        ];
    }
}
