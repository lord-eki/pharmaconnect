<?php

namespace App\Filament\Resources\Prescriptions;

use App\Filament\Resources\Prescriptions\Pages\ManagePrescriptions;
use App\Models\Prescription;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
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

class PrescriptionResource extends Resource
{
    protected static ?string $model = Prescription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPercentBadge;

    protected static string|UnitEnum|null $navigationGroup = 'Medicine Management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    TextInput::make('prescription_number')
                        ->required(),
                    Select::make('physician_id')
                        ->relationship('physician', 'name', fn ($query) => $query->role('physician') 
                        )
                        ->required(),

                    Select::make('patient_id')
                        ->relationship('patient', 'id')
                        ->required(),
                    Textarea::make('diagnosis')
                        ->default(null)
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->default(null)
                        ->columnSpanFull(),
                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'submitted' => 'Submitted',
                            'processing' => 'Processing',
                            'fulfilled' => 'Fulfilled',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('draft')
                        ->required(),
                    TextInput::make('total_amount')
                        ->numeric()
                        ->default(null),
                    Toggle::make('insurance_covered')
                        ->required(),
                    DateTimePicker::make('prescribed_at')
                        ->required(),
                    DateTimePicker::make('expires_at'),
                    DateTimePicker::make('fulfilled_at'),
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
                TextColumn::make('prescription_number')
                    ->searchable(),
                TextColumn::make('physician.name')
                    ->searchable(),
                TextColumn::make('patient.id')
                    ->searchable(),
                TextColumn::make('status'),
                TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('insurance_covered')
                    ->boolean(),
                TextColumn::make('prescribed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('fulfilled_at')
                    ->dateTime()
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
            'index' => ManagePrescriptions::route('/'),
        ];
    }
}
