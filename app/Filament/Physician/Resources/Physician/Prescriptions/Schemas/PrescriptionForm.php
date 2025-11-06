<?php

namespace App\Filament\Physician\Resources\Physician\Prescriptions\Schemas;

use App\Models\Medicine;
use App\Models\Patient;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Filament\Schemas\Components\Tabs;



class PrescriptionForm
{
 public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Prescription')
                    ->tabs([
                        Tabs\Tab::make('Patient Information')
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
                                    ->preload(false) 
                                    ->required()
                                    ->createOptionForm([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('first_name')
                                                    ->required()
                                                    ->maxLength(255),
                                                TextInput::make('last_name')
                                                    ->required()
                                                    ->maxLength(255),
                                                DatePicker::make('date_of_birth')
                                                    ->required()
                                                    ->maxDate(now()),
                                                Select::make('gender')
                                                    ->options([
                                                        'male' => 'Male',
                                                        'female' => 'Female',
                                                    ])
                                                    ->required(),
                                                TextInput::make('phone')
                                                    ->tel()
                                                    ->maxLength(255),
                                                TextInput::make('email')
                                                    ->email()
                                                    ->maxLength(255),
                                                TextInput::make('county')
                                                    ->maxLength(255),
                                                TextInput::make('city')
                                                    ->maxLength(255),
                                                Textarea::make('address')
                                                    ->columnSpanFull(),
                                                TextInput::make('insurance_number')
                                                    ->maxLength(255),
                                                TextInput::make('insurance_provider')
                                                    ->maxLength(255),
                                                Textarea::make('allergies')
                                                    ->columnSpanFull()
                                                    ->placeholder('List any known allergies'),
                                                Textarea::make('medical_conditions')
                                                    ->columnSpanFull()
                                                    ->placeholder('List any existing medical conditions'),
                                            ]),
                                    ])
                                    ->live(onBlur: true) 
                                    ->afterStateUpdated(function ($state) {
                                        
                                        
                                        $patient = Cache::remember(
                                            "patient_info_{$state}",
                                            3600, 
                                            fn () => Patient::select('id', 'allergies', 'medical_conditions')
                                                ->find($state)
                                        );
                                            
                                     
                                    }),
                  
                            ]),

                        Tabs\Tab::make('Diagnosis & Details')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Textarea::make('diagnosis')
                                    ->label('Diagnosis')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                
                                Textarea::make('notes')
                                    ->label('Prescription Notes')
                                    ->rows(2)
                                    ->columnSpanFull()
                                    ->placeholder('Additional notes or instructions'),
                                
                                Toggle::make('insurance_covered')
                                    ->label('Insurance Coverage')
                                    ->helperText('Does this prescription have insurance coverage?')
                                    ->default(false),
                            ]),

                        Tabs\Tab::make('Medicines')
                            ->icon('heroicon-o-beaker')
                            ->schema([
                                Repeater::make('items')
                                    ->relationship('items')
                                    ->schema([
                                        Select::make('medicine_id')
                                            ->label('Medicine')
                                            ->options(fn () => self::getCachedMedicineOptions())
                                            ->searchable()
                                            ->required()
                                            ->columnSpan(2)
                                            ->live(onBlur: true) // CHANGED: Only trigger on blur
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if (!$state) return;
                                                
                                                $quantity = $get('quantity') ?: 1;
                                                $priceData = self::getMedicinePricing($state, $quantity);
                                                
                                                $set('unit_price', $priceData['unit_price']);
                                                $set('total_price', $priceData['unit_price'] * $quantity);
                                            }),

                                        TextInput::make('quantity')
                                            ->numeric()
                                            ->required()
                                            ->minValue(1)
                                            ->default(1)
                                            ->live(onBlur: true) // CHANGED: Only trigger on blur
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if (!$state || $state <= 0) {
                                                    $set('unit_price', 0);
                                                    $set('total_price', 0);
                                                    return;
                                                }
                                                
                                                $medicineId = $get('medicine_id');
                                                if (!$medicineId) return;
                                                
                                                $priceData = self::getMedicinePricing($medicineId, $state);
                                                
                                                $set('unit_price', $priceData['unit_price']);
                                                $set('total_price', $priceData['unit_price'] * $state);
                                            }),

                                        TextInput::make('unit_price')
                                            ->label('Est. Unit Price')
                                            ->numeric()
                                            ->prefix('KES')
                                            ->disabled()
                                            ->dehydrated(),

                                        TextInput::make('total_price')
                                            ->label('Est. Total')
                                            ->numeric()
                                            ->prefix('KES')
                                            ->disabled()
                                            ->dehydrated(),

                                        Textarea::make('dosage_instructions')
                                            ->required()
                                            ->placeholder('e.g., Take 1 tablet twice daily after meals')
                                            ->rows(2)
                                            ->columnSpanFull(),

                                        TextInput::make('frequency')
                                            ->placeholder('e.g., Twice daily, Every 8 hours')
                                            ->maxLength(255),

                                        TextInput::make('duration_days')
                                            ->label('Duration (Days)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->suffix('days'),

                                        Textarea::make('notes')
                                            ->label('Medicine Notes')
                                            ->rows(2)
                                            ->columnSpanFull()
                                            ->placeholder('Special instructions for this medicine'),
                                    ])
                                    ->columns(3)
                                    ->defaultItems(1)
                                    ->addActionLabel('Add Medicine')
                                    ->collapsible()
                                    ->cloneable()
                                    ->reorderable(false) // Disable reordering to improve performance
                                    ->itemLabel(fn (array $state): ?string => 
                                        isset($state['medicine_id']) && $state['medicine_id']
                                            ? self::getMedicineName($state['medicine_id'])
                                            : null
                                    ),
                            ]),

                        Tabs\Tab::make('Summary')
                            ->icon('heroicon-o-calculator')
                            ->schema([
                                Placeholder::make('estimated_total')
                                    ->label('Estimated Total Cost')
                                    ->content(function (Get $get) {
                                        $items = $get('items') ?? [];
                                        $total = collect($items)->sum('total_price');
                                        return 'KES ' . number_format($total, 2);
                                    }),
                                
                                Placeholder::make('medicine_count')
                                    ->label('Total Medicines')
                                    ->content(function (Get $get) {
                                        $items = $get('items') ?? [];
                                        return count($items) . ' medicine(s)';
                                    }),
                            ])
                            ->visible(fn (Get $get) => !empty($get('items'))),
                    ])
                    ->columnSpanFull()
                    ->persistTab()
                    ->persistTabInQueryString(),
            ]);
    }

    /**
     * OPTIMIZED: Cache medicine options with longer TTL
     */
    protected static function getCachedMedicineOptions(): array
    {
        return Cache::remember('medicine_options_v3', 3600, function () {
            return Medicine::query()
                ->select(['id', 'generic_name', 'brand_name', 'strength', 'dosage_form'])
                ->where('is_active', true)
                ->orderBy('generic_name')
                ->limit(1000) // Increased limit
                ->get()
                ->mapWithKeys(function ($medicine) {
                    $brandInfo = $medicine->brand_name ? " ({$medicine->brand_name})" : '';
                    $label = "{$medicine->generic_name}{$brandInfo} - {$medicine->strength} - {$medicine->dosage_form}";
                    return [$medicine->id => $label];
                })
                ->toArray();
        });
    }

    /**
     * OPTIMIZED: Get medicine name with caching
     */
    protected static function getMedicineName(int $medicineId): ?string
    {
        return Cache::remember("medicine_name_{$medicineId}_v2", 3600, function () use ($medicineId) {
            return Medicine::where('id', $medicineId)->value('generic_name');
        });
    }

    /**
     * OPTIMIZED: Get cached medicine pricing with batch support
     */
    protected static function getMedicinePricing(int $medicineId, int $quantity = 1): array
    {
        if ($quantity <= 0) {
            $quantity = 1;
        }

        // Simplified cache key - no quantity rounding needed
        $cacheKey = "medicine_price_{$medicineId}_v3";
        
        try {
            return Cache::remember($cacheKey, 600, function () use ($medicineId, $quantity) {
                $lowestPrice = DB::table('supplier_medicines')
                    ->select('unit_price')
                    ->where('medicine_id', $medicineId)
                    ->where('is_available', true)
                    ->where('stock_quantity', '>=', $quantity)
                    ->orderBy('unit_price', 'asc')
                    ->value('unit_price');
                
                return [
                    'unit_price' => $lowestPrice ?? 0,
                ];
            });
        } catch (\Exception $e) {
            \Log::error('Error fetching medicine pricing', [
                'medicine_id' => $medicineId,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);
            
            return ['unit_price' => 0];
        }
    }
}