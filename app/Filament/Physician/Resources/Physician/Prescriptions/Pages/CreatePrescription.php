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
use Filament\Forms\Components\View;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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

    // ──────────────────────────────────────────────────────────────────────────
    // Draft cache key — unique per physician session
    // ──────────────────────────────────────────────────────────────────────────

    protected function getDraftCacheKey(): string
    {
        return 'prescription_draft_' . Auth::id() . '_' . session()->getId();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Restore cached draft on page mount
    // ──────────────────────────────────────────────────────────────────────────

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $cached = Cache::get($this->getDraftCacheKey());

        if ($cached) {
            return array_merge($data, $cached);
        }

        return $data;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Draft is saved only via the Clear Draft action and on final create.
    // saveDraft() is available for explicit calls only — not hooked into Livewire lifecycle.
    // ──────────────────────────────────────────────────────────────────────────

    protected function saveDraft(): void
    {
        try {
            $data = $this->form->getRawState();
            Cache::put($this->getDraftCacheKey(), $data, now()->addHours(12));
        } catch (\Throwable $e) {
            // Non-fatal — silently skip if state isn't ready yet
        }
    }

    protected function clearDraft(): void
    {
        Cache::forget($this->getDraftCacheKey());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Wizard steps
    // ──────────────────────────────────────────────────────────────────────────

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

            Step::make('Diagnosis & Details')
                ->icon('heroicon-o-clipboard-document-list')
                ->schema([
                    Section::make()->schema([
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
                ]),

            Step::make('Medicines')
                ->icon('heroicon-o-beaker')
                ->schema([
                    // ── Column header row ─────────────────────────────────────
                    // Single Placeholder with raw HTML — no field names rendered,
                    // just a clean styled header that aligns with the repeater columns.
                    Placeholder::make('_medicine_table_header')
                        ->hiddenLabel()
                        ->dehydrated(false)
                        ->content(new \Illuminate\Support\HtmlString('
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
                        ->relationship('items')
                        ->hiddenLabel()            
                        ->schema([
                            Select::make('medicine_id')
                                ->hiddenLabel()
                                ->placeholder('Medicine…')
                                ->options(function (Get $get) {
                                    $selected = collect($get('../../items') ?? [])->pluck('medicine_id')->filter()->toArray();
                                    return collect(self::getCachedMedicineOptions())
                                        ->reject(fn ($id) => in_array($id, $selected))
                                        ->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if (!$state) return;
                                    $medicine = self::getMedicineDetails((int) $state);
                                    if ($medicine) {
                                        $set('_medicine_type', $medicine['measurement_type']);
                                        $set('_unit_label', $medicine['unit_label']);
                                    }
                                    foreach (['dose_amount', 'frequency', 'duration_days', '_preview_quantity', '_preview_unit_price', '_preview_total_price'] as $field) {
                                        $set($field, null);
                                    }
                                })
                                ->columnSpan(3),

                            TextInput::make('dose_amount')
                                ->hiddenLabel()
                                ->placeholder(fn (Get $get) => $get('_medicine_type') === 'volume' ? 'ml' : 'units')
                                ->numeric()->required()->minValue(0.1)->step(0.1)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::refreshPreviews($get, $set);
                                })
                                ->columnSpan(1),

                            Select::make('frequency')
                                ->hiddenLabel()
                                ->placeholder('Frequency…')
                                ->options([
                                    'OD'    => 'OD – once daily',
                                    'BDS'   => 'BDS – twice daily',
                                    'TDS'   => 'TDS – three times daily',
                                    'QID'   => 'QID – four times daily',
                                    'Nocte' => 'Nocte – once at night',
                                    'Stat'  => 'Stat – immediately',
                                    'PRN'   => 'PRN – as required',
                                ])
                                ->required()->searchable()->live()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::refreshPreviews($get, $set);
                                })
                                ->columnSpan(2),

                            TextInput::make('duration_days')
                                ->hiddenLabel()
                                ->placeholder('Days')
                                ->numeric()->required()->minValue(1)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::refreshPreviews($get, $set);
                                })
                                ->columnSpan(1),

                            TextInput::make('_preview_quantity')
                                ->hiddenLabel()
                                ->disabled()->dehydrated(false)
                                ->placeholder('—')
                                ->columnSpan(1),

                            TextInput::make('_preview_unit_price')
                                ->hiddenLabel()
                                ->disabled()->dehydrated(false)
                                ->placeholder('—')
                                ->columnSpan(1),

                            TextInput::make('_preview_total_price')
                                ->hiddenLabel()
                                ->disabled()->dehydrated(false)
                                ->placeholder('—')
                                ->columnSpan(1),

                            // Instructions ─ span 2
                            Textarea::make('dosage_instructions')
                                ->hiddenLabel()
                                ->required()
                                ->placeholder('e.g. Take with food…')
                                ->rows(1)
                                ->columnSpan(2),

                            // Hidden meta — UI only, not persisted
                            TextInput::make('_medicine_type')->hidden()->dehydrated(false),
                            TextInput::make('_unit_label')->hidden()->dehydrated(false),
                        ])
                        ->columns(12)          // 12-col grid matching the header row
                        ->defaultItems(1)
                        ->addActionLabel('+ Add Medicine')
                        ->collapsible(false)   // keep rows flat for the table feel
                        ->cloneable()
                        ->reorderable(false)
                        ->deleteAction(fn ($action) => $action)
                        ->itemLabel(null),     // no accordion labels — rows are already visible
                ]),

            Step::make('Summary')
                ->icon('heroicon-o-calculator')
                ->schema([
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
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Live preview — UI only, nothing here reaches the DB
    // ──────────────────────────────────────────────────────────────────────────

    protected static function refreshPreviews(Get $get, Set $set): void
    {
        $medicineId  = $get('medicine_id');
        $doseAmount  = floatval($get('dose_amount'));
        $frequency   = $get('frequency');
        $duration    = intval($get('duration_days'));

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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['physician_id'] = auth()->id();
        $data['status']       = 'draft';
        return $data;
    }

    protected function afterCreate(): void
    {
        // Clear the draft now that the prescription is saved
        $this->clearDraft();

        if ($this->getRecord()->items()->exists()) {
            $this->getRecord()->updateTotalAmount();
        }
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearDraft')
                ->label('Clear Draft')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->outlined()
                ->requiresConfirmation()
                ->modalHeading('Discard draft?')
                ->modalDescription('All unsaved prescription data will be lost.')
                ->action(function () {
                    $this->clearDraft();
                    $this->form->fill([]);
                    Notification::make()->warning()->title('Draft cleared')->send();
                }),

            Action::make('back')
                ->url($this->getResource()::getUrl('index'))
                ->outlined()
                ->color('gray'),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Cached data helpers
    // ──────────────────────────────────────────────────────────────────────────

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

    protected static function getMedicineName(int $medicineId): ?string
    {
        return Cache::remember("medicine_name_{$medicineId}_v2", 3600,
            fn () => Medicine::where('id', $medicineId)->value('generic_name')
        );
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