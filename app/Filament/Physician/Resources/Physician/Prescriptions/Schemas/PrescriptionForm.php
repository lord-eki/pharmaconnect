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
            ->components([
                // ── Step 1: Patient Information ───────────────────────────────
                Section::make('Patient Information')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Select::make('patient_id')
                            ->label('Select Patient')
                            ->relationship(
                                name: 'patient',
                                titleAttribute: 'first_name',
                                modifyQueryUsing: fn (Builder $query) => $query
                                    ->where('physician_id', Auth::id())
                                    ->select(['id', 'first_name', 'last_name', 'patient_number'])
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} - {$record->patient_number}")
                            ->searchable(['first_name', 'last_name', 'patient_number'])
                            ->preload(true)
                            ->required()
                            ->createOptionForm([
                                Grid::make(2)->schema([
                                    TextInput::make('first_name')->required()->maxLength(255),
                                    TextInput::make('last_name')->required()->maxLength(255),
                                    DatePicker::make('date_of_birth')->required()->maxDate(now()),
                                    Select::make('gender')->options(['male' => 'Male', 'female' => 'Female'])->required(),
                                    TextInput::make('phone')->tel()->maxLength(255),
                                    TextInput::make('email')->email()->maxLength(255),
                                    TextInput::make('county')->maxLength(255),
                                    TextInput::make('city')->maxLength(255),
                                    Textarea::make('address')->columnSpanFull(),
                                    Select::make('insurance_provider_id')
                                        ->label('Insurance Provider')
                                        ->options(InsuranceProvider::query()->pluck('company_name', 'id')),
                                    TextInput::make('insurance_number')->maxLength(255),
                                    Textarea::make('allergies')->columnSpanFull()->placeholder('List any known allergies'),
                                    Textarea::make('medical_conditions')->columnSpanFull()->placeholder('List any existing medical conditions'),
                                ]),
                            ])
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set) {
                                $patient = Cache::remember("patient_info_{$state}", 3600,
                                    fn () => Patient::select('id', 'allergies', 'medical_conditions', 'insurance_provider_id', 'insurance_provider', 'insurance_number')->find($state)
                                );
                                if ($patient) {
                                    $hasInsurance = !empty($patient->insurance_number) &&
                                                   (!empty($patient->insurance_provider_id) || !empty($patient->insurance_provider));
                                    $set('patient_has_insurance', $hasInsurance);
                                    if ($hasInsurance) $set('insurance_covered', true);
                                }
                            }),
                    ]),

                Section::make('Diagnosis & Details')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Textarea::make('diagnosis')->label('Diagnosis')->rows(1)->required()->columnSpanFull(),
                        Textarea::make('notes')->label('Prescription Notes')->rows(2)->required()->columnSpanFull()
                            ->placeholder('Additional notes or instructions'),
                        Toggle::make('insurance_covered')
                            ->label('Insurance Coverage')
                            ->helperText('Does this prescription have insurance coverage?')
                            ->default(false)
                            ->visible(fn (Get $get) => $get('patient_has_insurance') === true)
                            ->live(),
                    ])->columns(3),

                Section::make('Medicines')
                    ->icon('heroicon-o-beaker')
                    ->schema([
                        Placeholder::make('_medicine_table_header')
                            ->hiddenLabel()
                            ->dehydrated(false)
                            ->content(new HtmlString('
                                <div style="display:grid;grid-template-columns:3fr 1fr 2fr 1fr 1fr 1fr 1fr 2fr;gap:0.5rem;padding:0.35rem 0.75rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.5rem 0.5rem 0 0;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;">
                                    <span>Medicine</span>
                                    <span>Dose</span>
                                    <span>Frequency</span>
                                    <span>Days</span>
                                    <span>Qty</span>
                                    <span>Unit (KES)</span>
                                    <span>Total (KES)</span>
                                    <span>Instructions</span>
                                </div>
                            ')),

                        Repeater::make('items')
                            ->label('')
                            ->relationship('items')
                            ->schema([
                                Grid::make(8)->schema([
                                    Select::make('medicine_id')
                                        ->label('Medicine')
                                        ->options(fn () => self::getCachedMedicineOptions())
                                        ->searchable()
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, Set $set) => self::refreshPreviews($get, $set))
                                        ->columnSpan(3),

                                    TextInput::make('dose_amount')
                                        ->label('Dose')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0.1)
                                        ->step(0.1)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, Set $set) => self::refreshPreviews($get, $set))
                                        ->columnSpan(1),

                                    Select::make('frequency')
                                        ->label('Frequency')
                                        ->options(['OD' => 'OD', 'BDS' => 'BDS', 'TDS' => 'TDS', 'QID' => 'QID', 'Stat' => 'Stat', 'PRN' => 'PRN', 'Nocte' => 'Nocte'])
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(fn (Get $get, Set $set) => self::refreshPreviews($get, $set))
                                        ->columnSpan(2),

                                    TextInput::make('duration_days')
                                        ->label('Days')
                                        ->numeric()
                                        ->required()
                                        ->minValue(1)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, Set $set) => self::refreshPreviews($get, $set))
                                        ->columnSpan(1),

                                    Placeholder::make('_preview_quantity')
                                        ->label('Qty')
                                        ->dehydrated(false)
                                        ->content(fn (Get $get) => $get('_preview_quantity') ?? '—')
                                        ->columnSpan(1),

                                    Placeholder::make('_preview_unit_price')
                                        ->label('Unit (KES)')
                                        ->dehydrated(false)
                                        ->content(fn (Get $get) => $get('_preview_unit_price') !== null
                                            ? number_format((float) $get('_preview_unit_price'), 2)
                                            : '—')
                                        ->columnSpan(1),

                                    Placeholder::make('_preview_total_price')
                                        ->label('Total (KES)')
                                        ->dehydrated(false)
                                        ->content(fn (Get $get) => $get('_preview_total_price') !== null
                                            ? number_format((float) $get('_preview_total_price'), 2)
                                            : '—')
                                        ->columnSpan(1),

                                    Textarea::make('instructions')
                                        ->label('Instructions')
                                        ->rows(1)
                                        ->placeholder('e.g. Take after meals')
                                        ->columnSpan(2),
                                ]),
                            ])
                            ->addActionLabel('+ Add Medicine')
                            ->minItems(1)
                            ->reorderable(false)
                            ->collapsible(),

                        // ── Summary placeholders ───────────────────────────────
                        Placeholder::make('estimated_total')
                            ->label('Estimated Total Cost')
                            ->content(function (Get $get) {
                                $total = collect($get('items') ?? [])->sum('_preview_total_price');
                                return 'KES ' . number_format((float) $total, 2);
                            }),

                        Placeholder::make('medicine_count')
                            ->label('Total Medicines')
                            ->content(fn (Get $get) => count($get('items') ?? []) . ' medicine(s)'),
                    ]),
            ]);
    }


    protected static function refreshPreviews(Get $get, Set $set): void
    {
        $medicineId = $get('medicine_id');
        $doseAmount = floatval($get('dose_amount'));
        $frequency  = $get('frequency');
        $duration   = intval($get('duration_days'));

        $frequencyMap = ['OD' => 1, 'Stat' => 1, 'PRN' => 1, 'Nocte' => 1, 'BDS' => 2, 'TDS' => 3, 'QID' => 4];
        $timesPerDay  = $frequencyMap[$frequency] ?? null;

        if (!$medicineId || !$doseAmount || !$timesPerDay || !$duration) {
            $set('_preview_quantity', null);
            $set('_preview_unit_price', null);
            $set('_preview_total_price', null);
            return;
        }

        $totalRequired = $doseAmount * $timesPerDay * $duration;
        $medicine      = self::getMedicineDetails((int) $medicineId);

        if (!$medicine) return;

        $quantity = $medicine['measurement_type'] === 'volume' && $medicine['volume_per_unit']
            ? (int) ceil($totalRequired / $medicine['volume_per_unit'])
            : (int) ceil($totalRequired);

        $priceData = self::getMedicinePricing((int) $medicineId, $quantity);

        $set('_preview_quantity', $quantity);
        $set('_preview_unit_price', $priceData['unit_price']);
        $set('_preview_total_price', round($priceData['unit_price'] * $quantity, 2));
    }

    protected static function getCachedMedicineOptions(): array
    {
        return Cache::remember('medicine_options_v4', 3600, function () {
            return Medicine::query()
                ->select(['id', 'generic_name', 'brand_name', 'strength', 'dosage_form', 'measurement_type', 'volume_per_unit'])
                ->where('is_active', true)
                ->orderBy('generic_name')
                ->limit(1000)
                ->get()
                ->mapWithKeys(function ($medicine) {
                    $brandInfo = $medicine->brand_name ? " ({$medicine->brand_name})" : '';
                    $typeInfo  = $medicine->measurement_type === 'volume' && $medicine->volume_per_unit
                        ? " - {$medicine->volume_per_unit}ml" : '';
                    return [$medicine->id => "{$medicine->generic_name}{$brandInfo} - {$medicine->strength} - {$medicine->dosage_form}{$typeInfo}"];
                })
                ->toArray();
        });
    }

    protected static function getMedicineDetails(int $medicineId): ?array
    {
        return Cache::remember("medicine_details_{$medicineId}_v1", 3600, function () use ($medicineId) {
            $medicine = Medicine::select(['id', 'measurement_type', 'volume_per_unit', 'unit_of_measurement', 'dosage_form'])->find($medicineId);
            if (!$medicine) return null;
            return [
                'measurement_type' => $medicine->measurement_type ?? 'discrete',
                'volume_per_unit'  => $medicine->volume_per_unit,
                'unit_label'       => $medicine->measurement_type === 'volume' ? 'ml' : ($medicine->unit_of_measurement ?? 'unit'),
            ];
        });
    }

    protected static function getMedicinePricing(int $medicineId, int $quantity = 1): array
    {
        if ($quantity <= 0) $quantity = 1;

        try {
            return Cache::remember("medicine_price_{$medicineId}_q{$quantity}_v5", 600, function () use ($medicineId, $quantity) {
                $supplierPrice = DB::table('supplier_medicines')
                    ->where('medicine_id', $medicineId)
                    ->where('is_available', true)
                    ->where('stock_quantity', '>=', $quantity)
                    ->orderBy('unit_price', 'asc')
                    ->value('unit_price');

                if (!$supplierPrice) return ['unit_price' => 0, 'supplier_price' => 0];

                $medicine = Medicine::find($medicineId);
                if (!$medicine) return ['unit_price' => $supplierPrice, 'supplier_price' => $supplierPrice];

                $pricing = app(PricingService::class)->calculateFinalPrice($supplierPrice, $medicine, $quantity);
                return ['unit_price' => $pricing['final_unit_price'], 'supplier_price' => $pricing['supplier_price']];
            });
        } catch (\Exception $e) {
            \Log::error('Error fetching medicine pricing', ['medicine_id' => $medicineId, 'quantity' => $quantity, 'error' => $e->getMessage()]);
            return ['unit_price' => 0, 'supplier_price' => 0];
        }
    }
}