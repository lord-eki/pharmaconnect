<?php

namespace App\Filament\Physician\Resources\Physician\Prescriptions\Pages;

use App\Filament\Physician\Resources\Physician\Prescriptions\PrescriptionResource;
use App\Models\InsuranceProvider;
use App\Models\Medicine;
use App\Models\Patient;
use App\Services\PricingService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CreatePrescription extends CreateRecord
{
    use HasWizard;

    protected static string $resource = PrescriptionResource::class;

    protected static bool $canCreateAnother = false;

    protected function getSteps()
    {
        return [
            Step::make('Patient Information')
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
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('first_name')->required()->maxLength(255),
                                    TextInput::make('last_name')->required()->maxLength(255),
                                    DatePicker::make('date_of_birth')->required()->maxDate(now()),
                                    Select::make('gender')->options([
                                        'male' => 'Male',
                                        'female' => 'Female',
                                    ])->required(),
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
                            $patient = Cache::remember(
                                "patient_info_{$state}",
                                3600,
                                fn () => Patient::select('id', 'allergies', 'medical_conditions', 'insurance_provider_id', 'insurance_provider', 'insurance_number')
                                    ->find($state)
                            );

                            if ($patient) {
                                $hasInsurance = ! empty($patient->insurance_number) &&
                                               (! empty($patient->insurance_provider_id) || ! empty($patient->insurance_provider));
                                $set('patient_has_insurance', $hasInsurance);

                                if ($hasInsurance) {
                                    $set('insurance_covered', true);
                                }
                            }
                        }),
                ]),

            Step::make('Diagnosis & Details')
                ->icon('heroicon-o-clipboard-document-list')
                ->schema([
                    Textarea::make('diagnosis')
                        ->label('Diagnosis')
                        ->rows(1)->required()
                        ->columnSpanFull(),

                    Textarea::make('notes')
                        ->label('Prescription Notes')
                        ->rows(2)->required()
                        ->columnSpanFull()
                        ->placeholder('Additional notes or instructions'),

                    Toggle::make('insurance_covered')
                        ->label('Insurance Coverage')
                        ->helperText('Does this prescription have insurance coverage?')
                        ->default(false)
                        ->visible(fn (Get $get) => $get('patient_has_insurance') === true),
                ]),

            Step::make('Medicines')
                ->icon('heroicon-o-beaker')
                ->schema([
                    Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Select::make('medicine_id')
                                ->label('Medicine')
                                ->options(function (Get $get) {
                                    $selected = collect($get('../../items') ?? [])
                                        ->pluck('medicine_id')
                                        ->filter()
                                        ->toArray();

                                    $allOptions = self::getCachedMedicineOptions();

                                    return collect($allOptions)
                                        ->reject(fn ($id, $label) => in_array($id, $selected))
                                        ->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    if (! $state) {
                                        return;
                                    }

                                    // Get medicine details
                                    $medicine = self::getMedicineDetails($state);

                                    if ($medicine) {
                                        $set('medicine_type', $medicine['measurement_type']);
                                        $set('volume_per_unit', $medicine['volume_per_unit']);
                                        $set('unit_label', $medicine['unit_label']);

                                        // Reset dosage fields
                                        $set('dose_amount', null);
                                        $set('frequency_per_day', null);
                                        $set('frequency', null);
                                        $set('duration_days', null);
                                        $set('quantity', null);
                                        $set('total_volume_required', null);
                                    }

                                    // Update pricing
                                    self::updatePricing($state, $get, $set);
                                })
                                ->columns(3),

                            // Dosage amount field - label changes based on medicine type
                            TextInput::make('dose_amount')
                                ->label(fn (Get $get) => $get('medicine_type') === 'volume'
                                        ? 'How many ml'
                                        : 'Dosage'
                                )
                                ->numeric()
                                ->required()
                                ->minValue(0.1)
                                ->step(0.1)
                                ->suffix(fn (Get $get) => $get('unit_label') ?? 'unit')
                                ->helperText(fn (Get $get) => $get('medicine_type') === 'volume'
                                        ? 'Volume per dose in ml'
                                        : 'Number of tablets/capsules per dose'
                                )
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, Get $get, Set $set) => self::calculateQuantity($get, $set)
                                ),

                            Select::make('frequency')
                                ->label('How often should it be taken? (Frequency)')
                                ->options([
                                    'OD' => 'OD (daily)',
                                    'Stat' => 'Stat (immediately)',
                                    'PRN' => 'PRN (as required)',
                                    'BDS' => 'BDS (every 12 hours / 2x per day)',
                                    'TDS' => 'TDS (every 8 hours / 3x per day)',
                                    'QID' => 'QID (every 6 hours / 4x per day)',
                                    'Nocte' => 'Nocte (once at night)',
                                    'Other' => 'Other',
                                ])
                                ->required()
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    // Map frequency codes to times per day
                                    $frequencyMap = [
                                        'OD' => 1,
                                        'Stat' => 1,
                                        'BDS' => 2,
                                        'TDS' => 3,
                                        'QID' => 4,
                                        'Nocte' => 1,
                                        'PRN' => 1, // Default for PRN
                                        'Other' => null,
                                    ];

                                    $timesPerDay = $frequencyMap[$state] ?? null;
                                    $set('frequency_per_day', $timesPerDay);

                                    // Recalculate quantity
                                    self::calculateQuantity($get, $set);
                                }),

                            // Custom frequency input (only shown when "Other" is selected)
                            TextInput::make('frequency_per_day')
                                ->label('Times per day')
                                ->numeric()
                                ->minValue(1)
                                ->required(fn (Get $get) => $get('frequency') === 'Other')
                                ->visible(fn (Get $get) => $get('frequency') === 'Other')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, Get $get, Set $set) => self::calculateQuantity($get, $set)
                                ),

                            // Duration in days
                            TextInput::make('duration_days')
                                ->label('For how many days')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->suffix('days')
                                ->helperText('Treatment duration')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, Get $get, Set $set) => self::calculateQuantity($get, $set)
                                ),
                            TextInput::make('unit_price')
                                ->label('Unit Price')
                                ->numeric()
                                ->prefix('KES')
                                ->disabled()
                                ->dehydrated()
                                ->visible(fn (Get $get) => $get('unit_price')),

                            // // Calculated fields display - showing what was calculated
                            // Grid::make(3)
                            //     ->schema([
                            //         Placeholder::make('total_volume_display')
                            //             ->label(fn (Get $get) => $get('medicine_type') === 'volume'
                            //                     ? 'Total ml Required'
                            //                     : 'Total Required'
                            //             )
                            //             ->content(fn (Get $get) => $get('total_volume_required')
                            //                     ? number_format($get('total_volume_required'), 1).' '.($get('unit_label') ?? 'units')
                            //                     : 'N/A'
                            //             )
                            //             ->visible(fn (Get $get) => $get('dose_amount') && $get('frequency_per_day') && $get('duration_days')
                            //             ),

                            //         Placeholder::make('quantity_display')
                            //             ->label(fn (Get $get) => $get('medicine_type') === 'volume'
                            //                     ? 'Bottles Needed'
                            //                     : 'Quantity'
                            //             )
                            //             ->content(fn (Get $get) => $get('quantity')
                            //                     ? number_format($get('quantity')).' '.($get('medicine_type') === 'volume' ? 'bottle(s)' : 'unit(s)')
                            //                     : 'N/A'
                            //             )
                            //             ->visible(fn (Get $get) => $get('quantity')),

                            //     ])
                            //     ->columnSpanFull(),

                            // Hidden fields to store calculated values
                            TextInput::make('quantity')
                                ->numeric()
                                ->disabled() // Read-only, auto-calculated
                                ->dehydrated(), // But still saved to database

                            TextInput::make('total_volume_required')
                                ->numeric()
                                ->hidden()
                                ->dehydrated(),

                            TextInput::make('frequency_per_day')
                                ->numeric()
                                ->hidden()
                                ->dehydrated()
                                ->visible(fn (Get $get) => $get('frequency') !== 'Other'),

                            TextInput::make('medicine_type')
                                ->hidden()
                                ->dehydrated(false),

                            TextInput::make('volume_per_unit')
                                ->hidden()
                                ->dehydrated(false),

                            TextInput::make('unit_label')
                                ->hidden()
                                ->dehydrated(false),

                            TextInput::make('supplier_price')
                                ->numeric()
                                ->hidden()
                                ->dehydrated(false),

                            // Total Price - visible and editable
                            TextInput::make('total_price')
                                ->label('Total Price')
                                ->numeric()
                                ->prefix('KES')
                                ->disabled()
                                ->dehydrated()
                                ->columns(3),

                            TextInput::make('dosage_instructions')
                                ->required()
                                ->placeholder('e.g., Take with food, avoid alcohol')
                                ->helperText('Additional instructions for the patient')
                                ->columns(3),
                        ])
                        ->columns(2)
                        ->defaultItems(1)
                        ->addActionLabel('Add Medicine')
                        ->collapsible()
                        ->cloneable()
                        ->reorderable(false)
                        ->itemLabel(fn (array $state): ?string => isset($state['medicine_id']) && $state['medicine_id']
                                ? self::getMedicineName($state['medicine_id'])
                                : null
                        ),
                ]),

            Step::make('Summary')
                ->icon('heroicon-o-calculator')
                ->schema([
                    Placeholder::make('estimated_total')
                        ->label('Estimated Total Cost')
                        ->content(function (Get $get) {
                            $items = $get('items') ?? [];
                            $total = collect($items)->sum('total_price');

                            return 'KES '.number_format($total, 2);
                        }),

                    Placeholder::make('medicine_count')
                        ->label('Total Medicines')
                        ->content(function (Get $get) {
                            $items = $get('items') ?? [];

                            return count($items).' medicine(s)';
                        }),
                ]),
        ];
    }

    /**
     * Calculate quantity based on dosage parameters
     */
    protected static function calculateQuantity(Get $get, Set $set): void
    {
        $doseAmount = floatval($get('dose_amount'));
        $frequency = intval($get('frequency_per_day'));
        $duration = intval($get('duration_days'));
        $medicineId = $get('medicine_id');

        if (! $doseAmount || ! $frequency || ! $duration || ! $medicineId) {
            return;
        }

        // Calculate total required
        $totalRequired = $doseAmount * $frequency * $duration;
        $set('total_volume_required', $totalRequired);

        // Get medicine details
        $medicine = self::getMedicineDetails($medicineId);

        if (! $medicine) {
            return;
        }

        // Calculate quantity based on medicine type
        if ($medicine['measurement_type'] === 'volume' && $medicine['volume_per_unit']) {
            // For volume-based: calculate bottles needed
            $quantity = ceil($totalRequired / $medicine['volume_per_unit']);
        } else {
            // For discrete: total is the quantity
            $quantity = ceil($totalRequired);
        }

        $set('quantity', $quantity);

        // Update pricing
        self::updatePricing($medicineId, $get, $set);
    }

    /**
     * Update pricing based on quantity
     */
    protected static function updatePricing($medicineId, Get $get, Set $set): void
    {
        $quantity = intval($get('quantity'));

        if (! $quantity || $quantity <= 0) {
            $set('supplier_price', 0);
            $set('unit_price', 0);
            $set('total_price', 0);

            return;
        }

        $priceData = self::getMedicinePricing($medicineId, $quantity);

        $set('supplier_price', $priceData['supplier_price']);
        $set('unit_price', $priceData['unit_price']);
        $set('total_price', $priceData['unit_price'] * $quantity);
    }

    /**
     * Get medicine details including measurement type
     */
    protected static function getMedicineDetails(int $medicineId): ?array
    {
        return Cache::remember("medicine_details_{$medicineId}_v1", 3600, function () use ($medicineId) {
            $medicine = Medicine::select([
                'id',
                'measurement_type',
                'volume_per_unit',
                'unit_of_measurement',
                'dosage_form',
            ])->find($medicineId);

            if (! $medicine) {
                return null;
            }

            return [
                'measurement_type' => $medicine->measurement_type ?? 'discrete',
                'volume_per_unit' => $medicine->volume_per_unit,
                'unit_label' => $medicine->measurement_type === 'volume' ? 'ml' : ($medicine->unit_of_measurement ?? 'unit'),
            ];
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Prescription Created')
            ->body('The prescription has been saved as draft. You can submit it when ready.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['physician_id'] = auth()->id();
        $data['status'] = 'draft';

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->getRecord()->items()->exists()) {
            $this->getRecord()->updateTotalAmount();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->url($this->getResource()::getUrl('index'))
                ->outlined()
                ->color('gray'),
        ];
    }

    /**
     * Cache medicine options with longer TTL
     */
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
                    $typeInfo = $medicine->measurement_type === 'volume' && $medicine->volume_per_unit
                        ? " - {$medicine->volume_per_unit}ml"
                        : '';
                    $label = "{$medicine->generic_name}{$brandInfo} - {$medicine->strength} - {$medicine->dosage_form}{$typeInfo}";

                    return [$medicine->id => $label];
                })
                ->toArray();
        });
    }

    /**
     * Get medicine name with caching
     */
    protected static function getMedicineName(int $medicineId): ?string
    {
        return Cache::remember("medicine_name_{$medicineId}_v2", 3600, function () use ($medicineId) {
            return Medicine::where('id', $medicineId)->value('generic_name');
        });
    }

    /**
     * Get cached medicine pricing with markup applied
     */
    protected static function getMedicinePricing(int $medicineId, int $quantity = 1): array
    {
        if ($quantity <= 0) {
            $quantity = 1;
        }

        $cacheKey = "medicine_price_{$medicineId}_q{$quantity}_v5";

        try {
            return Cache::remember($cacheKey, 600, function () use ($medicineId, $quantity) {
                // Get lowest supplier price
                $supplierPrice = DB::table('supplier_medicines')
                    ->select('unit_price')
                    ->where('medicine_id', $medicineId)
                    ->where('is_available', true)
                    ->where('stock_quantity', '>=', $quantity)
                    ->orderBy('unit_price', 'asc')
                    ->value('unit_price');

                if (! $supplierPrice) {
                    return [
                        'unit_price' => 0,
                        'supplier_price' => 0,
                    ];
                }

                $medicine = Medicine::find($medicineId);
                if (! $medicine) {
                    return [
                        'unit_price' => $supplierPrice,
                        'supplier_price' => $supplierPrice,
                    ];
                }

                $pricingService = app(PricingService::class);
                $pricing = $pricingService->calculateFinalPrice($supplierPrice, $medicine, $quantity);

                return [
                    'unit_price' => $pricing['final_unit_price'],
                    'supplier_price' => $pricing['supplier_price'],
                ];
            });
        } catch (\Exception $e) {
            \Log::error('Error fetching medicine pricing', [
                'medicine_id' => $medicineId,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
            ]);

            return [
                'unit_price' => 0,
                'supplier_price' => 0,
            ];
        }
    }
}
