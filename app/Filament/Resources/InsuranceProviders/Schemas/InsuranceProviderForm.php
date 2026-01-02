<?php

namespace App\Filament\Resources\InsuranceProviders\Schemas;

use App\Models\User;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class InsuranceProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Basic Information')
                    ->schema([
                   
                        TextInput::make('company_name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->helperText('Official registered company name'),

                        TextInput::make('registration_number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Unique registration/license number'),

                        TextInput::make('contact_person')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Primary contact person name'),

                        TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('+254-XXX-XXXXXX')
                            ->live(onBlur: true),

                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true),

                        Textarea::make('address')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Complete physical address'),

                        TextInput::make('website')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://www.example.com'),

                        Toggle::make('is_active')
                            ->label('Active Status')
                            ->helperText('Enable or disable this insurance provider')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('API Configuration')
                    ->description('Configure API integration settings (optional)')
                    ->schema([
                        TextInput::make('api_endpoint')
                            ->label('API Endpoint')
                            ->url()
                            ->placeholder('https://api.provider.com/claims')
                            ->maxLength(255),

                        TextInput::make('api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->helperText('API key will be encrypted in database'),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(),

                Section::make('Branding & Customization')
                    ->description('Configure how claim forms will appear for this insurance provider')
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Company Logo')
                            ->image()
                            ->disk('public')
                            ->directory('insurance-logos')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                            ->helperText('Upload company logo (PNG or JPG, max 2MB). Recommended: 400x150px')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '3:2',
                            ])
                            ->live(onBlur: true)
                            ->columnSpanFull(),

                        Textarea::make('header_text')
                            ->label('Claim Form Header Text')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('Text to display at the top of claim forms')
                            ->helperText('Leave empty to use: "Insurance Claim Form - [Company Name]"')
                            ->live(onBlur: true)
                            ->columnSpanFull(),

                        Textarea::make('footer_text')
                            ->label('Claim Form Footer Text')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('Text to display at the bottom of claim forms')
                            ->helperText('Leave empty to use default contact information')
                            ->live(onBlur: true)
                            ->columnSpanFull(),

                        ColorPicker::make('primary_color')
                            ->label('Primary Brand Color')
                            ->default('#000000')
                            ->helperText('Used for headers and main elements')
                            ->live(onBlur: true),

                        ColorPicker::make('secondary_color')
                            ->label('Secondary Brand Color')
                            ->default('#666666')
                            ->helperText('Used for secondary text and borders')
                            ->live(onBlur: true),

                        // Live Preview Section
                        // Placeholder::make('branding_preview')
                        //     ->label('Branding Preview')
                        //     ->content(function (Get $get) {
                        //         $companyName = $get('company_name') ?: 'Your Company Name';
                        //         $headerText = $get('header_text') ?: "Insurance Claim Form - {$companyName}";
                        //         $footerText = $get('footer_text') ?: "Contact: {$get('phone')} | Email: {$get('email')} | {$get('website')}";
                        //         $primaryColor = $get('primary_color') ?: '#000000';
                        //         $secondaryColor = $get('secondary_color') ?: '#666666';

                        //         return view('filament.components.insurance-branding-preview', [
                        //             'companyName' => $companyName,
                        //             'headerText' => $headerText,
                        //             'footerText' => $footerText,
                        //             'primaryColor' => $primaryColor,
                        //             'secondaryColor' => $secondaryColor,
                        //             'logoPath' => $get('logo_path'),
                        //         ]);
                        //     })
                        //     ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsible(),
            ]);
    }
}