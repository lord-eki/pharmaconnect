<?php

namespace App\Filament\Resources\Physicians\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PhysicianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->options(function () {
                        return User::whereHas('roles', fn($q) => $q->where('name', 'Physician'))
                            ->whereDoesntHave('physician')
                            ->pluck('name', 'id');
                    }),
                TextInput::make('license_number')
                    ->required(),
                DatePicker::make('license_expiry_date'),
                TextInput::make('medical_council_registration')
                    ->default(null),
                TextInput::make('specialization')
                    ->default(null),
                TextInput::make('years_experience')
                    ->numeric()
                    ->default(null),
                Select::make('qualification_level')
                    ->options([
                        'diploma' => 'Diploma',
                        'degree' => 'Degree',
                        'masters' => 'Masters',
                        'phd' => 'Phd',
                        'fellowship' => 'Fellowship',
                    ])
                    ->default(null),
                TextInput::make('practice_name')
                    ->default(null),
                Textarea::make('practice_address')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('county')
                    ->default(null),
                TextInput::make('city')
                    ->default(null),
                TextInput::make('postal_code')
                    ->default(null),
                TextInput::make('practice_phone')
                    ->tel()
                    ->default(null),
                TextInput::make('practice_email')
                    ->email()
                    ->default(null),
                Select::make('practice_type')
                    ->options(['private' => 'Private', 'public' => 'Public', 'ngo' => 'Ngo', 'faith_based' => 'Faith based'])
                    ->default(null),
                Select::make('verification_status')
                    ->options([
                        'pending' => 'Pending',
                        'verified' => 'Verified',
                        'rejected' => 'Rejected',
                        'suspended' => 'Suspended',
                    ])
                    ->default('pending')
                    ->required(),
                DateTimePicker::make('verified_at'),
                TextInput::make('verified_by')
                    ->numeric()
                    ->default(null),
                Textarea::make('verification_notes')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('document_path')
                    ->default(null),
                TextInput::make('commission_rate')
                    ->required()
                    ->numeric()
                    ->default(5.0),
                TextInput::make('total_commissions_earned')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('total_prescriptions')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_fulfilled_prescriptions')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('prescription_preferences')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('allow_generic_substitution')
                    ->required(),
                Toggle::make('require_patient_consent')
                    ->required(),
                Textarea::make('notification_preferences')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->required(),
                Toggle::make('accepting_prescriptions')
                    ->required(),
                TimePicker::make('practice_start_time'),
                TimePicker::make('practice_end_time'),
                Textarea::make('working_days')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
