<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Resources\Settings\Pages\ManageSettings;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Set Delivery Fee';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Delivery')
                    ->description('Configure delivery charges applied to all prescriptions and external orders.')
                    ->icon('heroicon-o-truck')
                    ->schema([
                        TextInput::make('value')
                            ->label('Delivery Fee (KES)')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('KES')
                            ->required()
                            ->helperText('Flat fee added as the last line item on every order. Appears in insurance claims and patient delivery notes.')
                            ->visible(fn ($record) => $record?->key === 'delivery_fee'),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Setting')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('group')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'delivery' => 'info',
                        'billing' => 'success',
                        'general' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                TextColumn::make('value')
                    ->label('Current Value')
                    ->formatStateUsing(function ($record) {
                        return match ($record->key) {
                            'delivery_fee' => 'KES '.number_format((float) $record->value, 2),
                            default => $record->value,
                        };
                    })
                    ->weight('bold'),

                TextColumn::make('description')
                    ->wrap()
                    ->color('gray'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M d, Y H:i')
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
            ]) ->defaultSort('group')
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSettings::route('/'),
        ];
    }
}
