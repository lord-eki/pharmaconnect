<?php

namespace App\Filament\Physician\Resources\Physician\Prescriptions\Schemas;

use App\Models\InsuranceProvider;
use App\Models\Medicine;
use App\Models\Patient;
use App\Services\PricingService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class PrescriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([]);

    }
}