<?php

namespace App\Filament\Operation\Resources\Internals\Riders\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class RiderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                 Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Basic Information')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextInput::make('rider_code')
                                            ->label('Rider Code')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->placeholder('Auto-generated on save')
                                            ->helperText('Rider code will be automatically generated')
                                            ->visible(fn ($context) => $context === 'edit')
                                            ->columnSpan(2),

                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('first_name')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->prefixIcon('heroicon-o-user'),

                                                TextInput::make('last_name')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->prefixIcon('heroicon-o-user'),
                                                TextInput::make('password')->required()->prefixIcon('heroicon-o-lock-closed')->password()->minLength(8)->maxLength(255)->dehydrated(fn ($context) => $context === 'create')->visible(fn ($context) => $context === 'create'),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('phone')
                                                    ->tel()
                                                    ->required()
                                                    ->unique(ignoreRecord: true)
                                                    ->maxLength(255)
                                                    ->placeholder('+254 700 000 000')
                                                    ->telRegex('/^[+]?[0-9]{10,15}$/')
                                                    ->prefixIcon('heroicon-o-phone'),

                                                TextInput::make('email')
                                                    ->email()
                                                    ->maxLength(255)
                                                    ->prefixIcon('heroicon-o-envelope'),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('License & Vehicle')
                            ->icon('heroicon-o-truck')
                            ->schema([
                               Section::make()
                                    ->schema([
                                       Grid::make(2)
                                            ->schema([
                                               TextInput::make('license_number')
                                                    ->label('Driving License Number')
                                                    ->required()
                                                    ->unique(ignoreRecord: true)
                                                    ->maxLength(255)
                                                    ->placeholder('e.g., 12345678')
                                                    ->prefixIcon('heroicon-o-identification'),

                                               Select::make('vehicle_type')
                                                    ->required()
                                                    ->options([
                                                        'motorcycle' => 'Motorcycle',
                                                        'car' => 'Car',
                                                        'bicycle' => 'Bicycle',
                                                        'van' => 'Van',
                                                    ])
                                                    ->native(false)
                                                    ->prefixIcon('heroicon-o-truck'),
                                            ]),

                                       TextInput::make('vehicle_registration')
                                            ->label('Vehicle Registration Number')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('e.g., KAA 123B')
                                            ->prefixIcon('heroicon-o-hashtag')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Location & Status')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                               Section::make()
                                    ->schema([
                                       Grid::make(2)
                                            ->schema([
                                               TextInput::make('base_county')
                                                    ->label('Base County')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('e.g., Nairobi')
                                                    ->datalist([
                                                        'Nairobi',
                                                        'Mombasa',
                                                        'Kisumu',
                                                        'Nakuru',
                                                        'Eldoret',
                                                        'Thika',
                                                        'Malindi',
                                                        'Kitale',
                                                        'Garissa',
                                                        'Kakamega',
                                                    ])
                                                    ->prefixIcon('heroicon-o-map-pin'),

                                                TextInput::make('base_city')
                                                    ->label('Base City/Town')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('e.g., Westlands')
                                                    ->prefixIcon('heroicon-o-building-office'),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                Toggle::make('is_active')
                                                    ->label('Active Status')
                                                    ->default(true)
                                                    ->helperText('Whether the rider is active in the system')
                                                    ->inline(false),

                                                Toggle::make('is_available')
                                                    ->label('Availability')
                                                    ->default(true)
                                                    ->helperText('Whether the rider is available for assignments')
                                                    ->inline(false),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Performance')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make()
                                    ->description('Rider performance metrics (automatically updated)')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Placeholder::make('rating_display')
                                                    ->label('Current Rating')
                                                    ->content(fn ($record) => $record?->rating 
                                                        ? number_format($record->rating, 2) . ' / 5.00 ⭐' 
                                                        : 'No rating yet'),

                                                Placeholder::make('deliveries_display')
                                                    ->label('Total Deliveries')
                                                    ->content(fn ($record) => $record?->total_deliveries ?? 0),
                                            ]),

                                        Placeholder::make('performance_note')
                                            ->label('')
                                            ->content('Performance metrics are calculated automatically based on completed deliveries and customer feedback.')
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->visible(fn ($record) => $record !== null),
                    ])
                    ->columnSpanFull()
                    ->contained(false),
            ]);
    }
}
