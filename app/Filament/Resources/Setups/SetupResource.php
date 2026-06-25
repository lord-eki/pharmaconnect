<?php

namespace App\Filament\Resources\Setups;

use App\Filament\Resources\Setups\Pages\ManageSetups;
use App\Models\Setup;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;

class SetupResource extends Resource
{
    protected static ?string $model = Setup::class;

     protected static bool $canCreateAnother = false;


    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
         
            TextInput::make('company_name')
                ->label('Company Name')
                ->required(),
            TextInput::make('company_email')
                ->label('Company Email')
                ->email()
                ->required(),
            TextInput::make('company_phone')
                ->label('Company Phone')
                ->tel()
                ->required(),
            TextInput::make('company_address')
                ->label('Company Address')
                ->required(),   
                    FileUpload::make('logo_path')
                            ->label('Company Logo')
                            ->image()
                            ->disk('public')
                            ->directory('pharmaconnect/logos')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml'])
                            ->helperText('Upload company logo (PNG, JPG, or SVG, max 2MB). Recommended: 400x150px')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '3:2',
                            ])
                            ->live(onBlur: true)->columns(2),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_path')->label('Logo')->disk('public')->rounded(),
                TextColumn::make('company_name')->label('Company Name'),
                TextColumn::make('company_email')->label('Company Email'),
                TextColumn::make('company_phone')->label('Company Phone'),
                TextColumn::make('company_address')->label('Company Address'),

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
            'index' => ManageSetups::route('/'),
        ];
    }
}
