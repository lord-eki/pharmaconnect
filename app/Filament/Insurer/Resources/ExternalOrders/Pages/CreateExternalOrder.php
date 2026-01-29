<?php

namespace App\Filament\Insurer\Resources\ExternalOrders\Pages;

use App\Filament\Insurer\Resources\ExternalOrders\ExternalOrderResource;
use App\Models\Medicine;
use App\Services\PricingService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CreateExternalOrder extends CreateRecord
{
    use HasWizard;

    protected static string $resource = ExternalOrderResource::class;

    protected static bool $canCreateAnother = false;

    protected function getSteps(): array
    {
        return [
            Step::make('Recipient & Delivery')
                ->icon('heroicon-o-user')
                ->description('Enter recipient and delivery details')
                ->schema([
                    Section::make('Recipient Information')
                        ->schema([
                            TextInput::make('recipient_name')
                                ->label('Recipient Name')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(1),

                            TextInput::make('recipient_phone')
                                ->label('Phone Number')
                                ->tel()
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(1),

                            TextInput::make('recipient_email')
                                ->label('Email (Optional)')
                                ->email()
                                ->maxLength(255)
                                ->columnSpan(1),

                            TextInput::make('reference_number')
                                ->label('Reference Number')
                                ->helperText('Your internal reference number')
                                ->maxLength(255)
                                ->columnSpan(1),
                        ])
                        ->columns(2),

                    Section::make('Delivery Address')
                        ->schema([
                            Textarea::make('delivery_address')
                                ->label('Delivery Address')
                                ->required()
                                ->rows(3)
                                ->columnSpanFull()
                                ->placeholder('Enter the complete delivery address'),

                            TextInput::make('delivery_county')
                                ->label('County')
                                ->maxLength(255)
                                ->columnSpan(1),

                            TextInput::make('delivery_city')
                                ->label('City')
                                ->maxLength(255)
                                ->columnSpan(1),
                        ])
                        ->columns(2),

                  
                ])
                ->columns(2),

            Step::make('Select Medicines')
                ->icon('heroicon-o-beaker')
                ->schema([
                    Grid::make(12)
                        ->schema([
                            // Main content area - 8 columns
                            Section::make('Order Items')
                                ->schema([
                                    Repeater::make('items')
                                        ->relationship('items')
                                        ->label('')
                                        ->schema([
                                            Select::make('medicine_id')
                                                ->label('Medicine')
                                                ->helperText('Search by generic name, brand, or strength')
                                                ->options(function ($get) {
                                                    // Get already selected medicines to exclude them
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
                                                ->afterStateUpdated(function ($state, $set, $get) {
                                                    if (! $state) {
                                                        return;
                                                    }

                                                    $quantity = $get('quantity') ?: 1;
                                                    $priceData = self::getMedicinePricing($state, $quantity);

                                                    $set('supplier_price', $priceData['supplier_price']);
                                                    $set('unit_price', $priceData['unit_price']);
                                                    $set('total_price', $priceData['unit_price'] * $quantity);
                                                })
                                                ->columnSpan(2),

                                            TextInput::make('quantity')
                                                ->label('Quantity')
                                                ->numeric()
                                                ->required()
                                                ->minValue(1)
                                                ->default(1)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, $set, $get) {
                                                    if (! $state || $state <= 0) {
                                                        $set('supplier_price', 0);
                                                        $set('unit_price', 0);
                                                        $set('total_price', 0);

                                                        return;
                                                    }

                                                    $medicineId = $get('medicine_id');
                                                    if (! $medicineId) {
                                                        return;
                                                    }

                                                    $priceData = self::getMedicinePricing($medicineId, $state);

                                                    $set('supplier_price', $priceData['supplier_price']);
                                                    $set('unit_price', $priceData['unit_price']);
                                                    $set('total_price', $priceData['unit_price'] * $state);
                                                })
                                                ->columnSpan(1),

                                            TextInput::make('unit_price')
                                                ->label('Unit Price (Ksh)')
                                                ->numeric()
                                                ->disabled()
                                                ->dehydrated()
                                                ->columnSpan(1),

                                            TextInput::make('total_price')
                                                ->label('Line Total (Ksh)')
                                                ->numeric()
                                                ->disabled()
                                                ->dehydrated()
                                                ->extraAttributes(['class' => 'font-bold text-primary-600'])
                                                ->columnSpan(1),

                                            // Hidden field to store supplier price for order processing
                                            TextInput::make('supplier_price')
                                                ->numeric()
                                                ->default(0)
                                                ->required()
                                                ->hidden()
                                                ->dehydrated(true),
                                        ])
                                        ->columns(5)
                                        ->defaultItems(1)
                                        ->addActionLabel('Add Another Medicine')
                                        ->collapsible()
                                        ->cloneable()
                                        ->reorderable()
                                        ->itemLabel(fn (array $state): ?string => self::getMedicineName($state['medicine_id'] ?? null) ?? 'New Medicine')
                                        ->deleteAction(
                                            fn ($action) => $action
                                                ->requiresConfirmation()
                                                ->modalHeading('Remove Medicine')
                                                ->modalDescription('Are you sure you want to remove this medicine from your order?')
                                        )
                                        ->minItems(1)
                                        ->columnSpanFull(),
                                ])
                                ->columnSpan(8),

                            Section::make('Order Summary')
                                ->schema([
                                    Placeholder::make('order_summary')
                                        ->label('')
                                        ->content(function ($get) {
                                            $items = $get('items') ?? [];
                                            
                                            if (empty($items)) {
                                                return new \Illuminate\Support\HtmlString('
                                                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                                        <div class="text-lg mb-2">No items yet</div>
                                                        <div class="text-sm">Add medicines to see order summary</div>
                                                    </div>
                                                ');
                                            }

                                            $subtotal = 0;
                                            $itemCount = 0;
                                            $totalQuantity = 0;

                                            $html = '<div class="space-y-4">';
                                            
                                            // Items list
                                            $html .= '<div class="space-y-3">';
                                            foreach ($items as $item) {
                                                $medicineId = $item['medicine_id'] ?? null;
                                                $quantity = $item['quantity'] ?? 0;
                                                $unitPrice = $item['unit_price'] ?? 0;
                                                $totalPrice = $item['total_price'] ?? 0;

                                                if (! $medicineId || $quantity <= 0) {
                                                    continue;
                                                }

                                                $itemCount++;
                                                $totalQuantity += $quantity;
                                                $subtotal += $totalPrice;

                                                $medicineName = self::getMedicineName($medicineId);

                                                $html .= '<div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">';
                                                $html .= '<div class="font-medium text-sm text-gray-900 dark:text-gray-100 mb-1">'.e($medicineName).'</div>';
                                                $html .= '<div class="flex justify-between items-center text-xs text-gray-600 dark:text-gray-400">';
                                                $html .= '<span>Qty: '.$quantity.' × KES '.number_format($unitPrice, 2).'</span>';
                                                $html .= '<span class="font-semibold text-gray-900 dark:text-gray-100">KES '.number_format($totalPrice, 2).'</span>';
                                                $html .= '</div>';
                                                $html .= '</div>';
                                            }
                                            $html .= '</div>';

                                            // Summary statistics
                                            $html .= '<div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4 space-y-2">';
                                            
                                            $html .= '<div class="flex justify-between text-sm">';
                                            $html .= '<span class="text-gray-600 dark:text-gray-400">Total Items:</span>';
                                            $html .= '<span class="font-medium text-gray-900 dark:text-gray-100">'.$itemCount.'</span>';
                                            $html .= '</div>';
                                            
                                            $html .= '<div class="flex justify-between text-sm">';
                                            $html .= '<span class="text-gray-600 dark:text-gray-400">Total Quantity:</span>';
                                            $html .= '<span class="font-medium text-gray-900 dark:text-gray-100">'.$totalQuantity.'</span>';
                                            $html .= '</div>';
                                            
                                            $html .= '<div class="flex justify-between items-center pt-2 border-t border-gray-200 dark:border-gray-700">';
                                            $html .= '<span class="font-semibold text-gray-900 dark:text-gray-100">Order Total:</span>';
                                            $html .= '<span class="font-bold text-xl text-primary-600 dark:text-primary-400">KES '.number_format($subtotal, 2).'</span>';
                                            $html .= '</div>';
                                            
                                            $html .= '</div>';

                                            $html .= '</div>';

                                            return new \Illuminate\Support\HtmlString($html);
                                        })
                                        ->columnSpanFull(),
                                ])
                                ->columnSpan(4)
                                ->extraAttributes(['class' => 'sticky top-4']),
                        ]),
                ]),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Order Created Successfully')
            ->body('Your order has been created as a draft. Review it and click "Submit Order" when ready.')
            ->duration(5000);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_user_id'] = auth()->id();
        $data['status'] = 'draft';

        // Set insurance provider if user has one
        if (auth()->user()->insuranceProvider) {
            $data['insurance_provider_id'] = auth()->user()->insuranceProvider->id;
        }

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
                ->label('Back to List')
                ->url($this->getResource()::getUrl('index'))
                ->outlined()
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
        ];
    }

    /**
     * Cache medicine options with stock availability
     */
    protected static function getCachedMedicineOptions(): array
    {
        return Cache::remember('medicine_options_external_v1', 3600, function () {
            return Medicine::query()
                ->select(['id', 'generic_name', 'brand_name', 'strength', 'dosage_form'])
                ->where('is_active', true)
                ->withStock(1) 
                ->orderBy('generic_name')
                ->limit(1000)
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
     * Get medicine name with caching for display
     */
    protected static function getMedicineName(?int $medicineId): ?string
    {
        if (! $medicineId) {
            return null;
        }

        return Cache::remember("medicine_name_{$medicineId}_v2", 3600, function () use ($medicineId) {
            $medicine = Medicine::select(['generic_name', 'brand_name', 'strength', 'dosage_form'])
                ->find($medicineId);

            if (! $medicine) {
                return 'Unknown Medicine';
            }

            $brandInfo = $medicine->brand_name ? " ({$medicine->brand_name})" : '';

            return "{$medicine->generic_name}{$brandInfo} - {$medicine->strength}";
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

        $cacheKey = "medicine_price_{$medicineId}_q{$quantity}_v1";

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

                // Apply pricing service markup
                $pricingService = app(PricingService::class);
                $pricing = $pricingService->calculateFinalPrice($supplierPrice, $medicine, $quantity);

                return [
                    'unit_price' => $pricing['final_unit_price'],
                    'supplier_price' => $pricing['supplier_price'],
                ];
            });
        } catch (\Exception $e) {
            \Log::error('Error fetching medicine pricing for external order', [
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

    /**
     * Get the submit button label
     */
    protected function getSubmitFormAction(): Action
    {
        return parent::getSubmitFormAction()
            ->label('Create Order')
            ->icon('heroicon-o-check-circle');
    }

    /**
     * Customize wizard behavior
     */
    public function hasSkippableSteps(): bool
    {
        return false;
    }


}
