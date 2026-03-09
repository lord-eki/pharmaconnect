<?php

namespace App\Filament\Resources\Medicines;

use App\Filament\Resources\Medicines\Pages\ManageMedicines;
use App\Models\Medicine;
use App\Models\MedicineCategory;
use BackedEnum;
use Filament\Schemas\Components\Section;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Components\Grid;


class MedicineResource extends Resource
{
    protected static ?string $model = Medicine::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static string|UnitEnum|null $navigationGroup = 'Medicine Management';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([

                // ── Row 1: Basic Information — full width ──────────────────────────
                Section::make('Basic Information')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('category_id')
                                    ->label('Category')
                                    ->options(MedicineCategory::pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                TextInput::make('generic_name')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),

                                TextInput::make('brand_name')
                                    ->maxLength(255),

                                TextInput::make('strength')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., 500mg, 10mg/ml'),

                                Select::make('dosage_form')
                                    ->options([
                                        'Tablet'      => 'Tablet',
                                        'Capsule'     => 'Capsule',
                                        'Syrup'       => 'Syrup',
                                        'Suspension'  => 'Suspension',
                                        'Injection'   => 'Injection',
                                        'Cream'       => 'Cream',
                                        'Ointment'    => 'Ointment',
                                        'Drops'       => 'Drops',
                                        'Inhaler'     => 'Inhaler',
                                        'Patch'       => 'Patch',
                                        'Suppository' => 'Suppository',
                                        'Solution'    => 'Solution',
                                    ])
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $volumeForms = ['Syrup', 'Suspension', 'Injection', 'Solution', 'Drops'];
                                        if (in_array($state, $volumeForms)) {
                                            $set('measurement_type', 'volume');
                                        } else {
                                            $set('measurement_type', 'discrete');
                                        }
                                    }),

                                TextInput::make('manufacturer')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                    ]),

                // ── Row 2: Measurement Information — full width ────────────────────
                Section::make('Measurement Information')
                    ->description('Define how this medicine is measured and dispensed')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('measurement_type')
                                    ->label('Measurement Type')
                                    ->options([
                                        'discrete' => 'Discrete (Tablets/Capsules)',
                                        'volume'   => 'Volume-Based (Syrups/Injections)',
                                    ])
                                    ->required()
                                    ->default('discrete')
                                    ->live()
                                    ->helperText('How is this medicine measured?'),

                                TextInput::make('volume_per_unit')
                                    ->label('Volume per Bottle/Vial')
                                    ->numeric()
                                    ->suffix('ml')
                                    ->minValue(1)
                                    ->step(1)
                                    ->helperText('For syrups, injections - bottle/vial size in ml')
                                    ->visible(fn (Get $get) => $get('measurement_type') === 'volume')
                                    ->required(fn (Get $get) => $get('measurement_type') === 'volume'),

                                TextInput::make('unit_of_measurement')
                                    ->label('Unit of Measurement')
                                    ->maxLength(20)
                                    ->placeholder('tablets, capsules, units, etc.')
                                    ->helperText('Display unit for this medicine')
                                    ->visible(fn (Get $get) => $get('measurement_type') === 'discrete'),

                                TextInput::make('pack_size')
                                    ->maxLength(255)
                                    ->placeholder('e.g., 10 tablets per strip, 30 capsules per bottle')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                // ── Row 3: Medicine Details + Regulatory Info — side by side ───────
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([

                        // Left column — Medicine Details (active ingredients only)
                        Section::make('Medicine Details')
                            ->schema([
                                Textarea::make('active_ingredients')
                                    ->required()
                                    ->rows(4)
                                    ->columnSpanFull()
                                    ->placeholder('List all active ingredients'),
                            ]),

                        // Right column — Regulatory Information
                        Section::make('Regulatory Information')
                            ->schema([
                                TextInput::make('ppb_registration_number')
                                    ->label('PPB Registration Number')
                                    ->maxLength(255),

                                Toggle::make('prescription_required')
                                    ->label('Prescription Required')
                                    ->default(true)
                                    ->inline(false),

                                Toggle::make('controlled_substance')
                                    ->label('Controlled Substance')
                                    ->default(false)
                                    ->inline(false),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->inline(false),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('generic_name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('brand_name')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->toggleable(),

                TextColumn::make('strength')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('dosage_form')
                    ->badge()
                    ->sortable(),

                TextColumn::make('measurement_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'discrete' => 'info',
                        'volume'   => 'success',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'discrete' => 'Discrete',
                        'volume'   => 'Volume',
                        default    => $state,
                    })
                    ->toggleable(),

                TextColumn::make('volume_per_unit')
                    ->label('Volume')
                    ->suffix(' ml')
                    ->toggleable()
                    ->sortable()
                    ->visible(fn ($record) => $record?->measurement_type === 'volume'),

                TextColumn::make('category.name')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('manufacturer')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->limit(20),

                IconColumn::make('prescription_required')
                    ->label('Rx Required')
                    ->boolean()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->preload(),

                SelectFilter::make('dosage_form')
                    ->options([
                        'Tablet'   => 'Tablet',
                        'Capsule'  => 'Capsule',
                        'Syrup'    => 'Syrup',
                        'Injection'=> 'Injection',
                    ]),

                SelectFilter::make('measurement_type')
                    ->label('Measurement Type')
                    ->options([
                        'discrete' => 'Discrete',
                        'volume'   => 'Volume-Based',
                    ]),

                SelectFilter::make('prescription_required')
                    ->label('Prescription Required')
                    ->options([
                        '1' => 'Yes',
                        '0' => 'No',
                    ]),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('generic_name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMedicines::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
}