<?php

namespace App\Filament\Physician\Resources\Physician\Orders\Pages;

use App\Filament\Physician\Resources\Physician\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_prescription')
                ->label('View Prescription')
                ->icon('heroicon-o-document-text')
                ->color('info'),
            // ->url(fn () => route('filament.physician.resources.prescriptions.view', $this->record->prescription)),

            Action::make('track_delivery')
                ->label('Track Delivery')
                ->icon('heroicon-o-map-pin')
                ->color('primary')
                ->visible(fn () => $this->record->delivery),
            // ->url(fn (): string => route('filament.physician.resources.deliveries.view', $this->record->delivery)),

            Action::make('print')
                ->label('Print LPO')
                ->icon('heroicon-o-printer')
                ->color('gray')
                 // ->url(fn () => route('orders.print', $this->record))
                ->openUrlInNewTab(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Order Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('order_number')
                                    ->label('Order Number (LPO)')
                                    ->copyable()
                                    ->icon('heroicon-o-clipboard-document')
                                    ->weight('bold')
                                    ->size('lg'),

                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'processing' => 'info',
                                        'shipped' => 'primary',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger',
                                    }),

                                TextEntry::make('ordered_at')
                                    ->label('Order Date')
                                    ->dateTime('M d, Y H:i'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('expected_delivery')
                                    ->label('Expected Delivery')
                                    ->dateTime('M d, Y H:i')
                                    ->color(fn ($record) => $record->is_overdue ? 'danger' : 'success')
                                    ->icon(fn ($record) => $record->is_overdue ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-clock'),

                                TextEntry::make('delivered_at')
                                    ->label('Actual Delivery')
                                    ->dateTime('M d, Y H:i')
                                    ->default('Not yet delivered')
                                    ->visible(fn ($record) => $record->delivered_at),
                            ]),
                    ]),

                Section::make('Prescription & Patient Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('prescription.prescription_number')
                                    ->label('Prescription Number')
                                    ->copyable()
                                    // ->url(fn ($record): string => route('filament.physician.resources.prescriptions.view', $record->prescription))
                                    ->color('primary'),

                                TextEntry::make('prescription.patient.full_name')
                                    ->label('Patient Name')
                                    ->weight('bold'),

                                TextEntry::make('prescription.patient.patient_number')
                                    ->label('Patient Number')
                                    ->copyable(),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('prescription.patient.phone')
                                    ->label('Patient Phone')
                                    ->icon('heroicon-o-phone'),

                                TextEntry::make('prescription.patient.county')
                                    ->label('County')
                                    ->icon('heroicon-o-map-pin'),

                                TextEntry::make('prescription.patient.city')
                                    ->label('City'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Supplier Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('supplier.company_name')
                                    ->label('Company Name')
                                    ->weight('bold'),

                                TextEntry::make('supplier.contact_person')
                                    ->label('Contact Person'),

                                TextEntry::make('supplier.phone')
                                    ->label('Phone')
                                    ->icon('heroicon-o-phone'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('supplier.email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable(),

                                TextEntry::make('supplier.address')
                                    ->label('Address')
                                    ->icon('heroicon-o-map-pin'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Order Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Grid::make(5)
                                    ->schema([
                                        TextEntry::make('medicine.generic_name')
                                            ->label('Medicine')
                                            ->weight('bold')
                                            ->columnSpan(2),

                                        TextEntry::make('medicine.strength')
                                            ->label('Strength')
                                            ->badge()
                                            ->color('gray'),

                                        TextEntry::make('quantity')
                                            ->label('Qty')
                                            ->suffix(' units'),

                                        TextEntry::make('status')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'pending' => 'warning',
                                                'confirmed' => 'info',
                                                'shipped' => 'primary',
                                                'delivered' => 'success',
                                            }),
                                    ]),

                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('medicine.brand_name')
                                            ->label('Brand')
                                            ->default('Generic'),

                                        TextEntry::make('unit_price')
                                            ->label('Unit Price')
                                            ->money('KES'),

                                        TextEntry::make('total_price')
                                            ->label('Total')
                                            ->money('KES')
                                            ->weight('bold'),
                                    ]),
                            ])
                            ->contained(true),
                    ]),

                Section::make('Financial Summary')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('total_amount')
                                    ->label('Order Total')
                                    ->money('KES')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('success'),

                                TextEntry::make('delivery.delivery_fee')
                                    ->label('Delivery Fee')
                                    ->money('KES')
                                    ->default('N/A'),

                                TextEntry::make('grand_total')
                                    ->label('Grand Total')
                                    ->state(function ($record) {
                                        $deliveryFee = $record->delivery ? $record->delivery->delivery_fee : 0;

                                        return $record->total_amount + $deliveryFee;
                                    })
                                    ->money('KES')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('primary'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('commission.commission_amount')
                                    ->label('Your Commission')
                                    ->money('KES')
                                    ->color('success')
                                    ->icon('heroicon-o-banknotes')
                                    ->default('Pending calculation')
                                    ->visible(fn ($record) => $record->commission),

                                TextEntry::make('commission.status')
                                    ->label('Commission Status')
                                    ->badge()
                                    ->visible(fn ($record) => $record->commission),
                            ]),
                    ]),

                Section::make('Delivery Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('delivery.delivery_number')
                                    ->label('Delivery Number')
                                    ->copyable(),

                                TextEntry::make('delivery.status')
                                    ->label('Delivery Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'assigned' => 'info',
                                        'picked_up' => 'primary',
                                        'in_transit' => 'primary',
                                        'delivered' => 'success',
                                        'failed' => 'danger',
                                        'cancelled' => 'danger',
                                    }),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('delivery.rider.first_name')
                                    ->label('Rider')
                                    ->state(fn ($record) => $record->delivery && $record->delivery->rider
                                            ? $record->delivery->rider->first_name.' '.$record->delivery->rider->last_name
                                            : 'Not assigned'
                                    ),

                                TextEntry::make('delivery.rider.phone')
                                    ->label('Rider Phone')
                                    ->icon('heroicon-o-phone')
                                    ->default('N/A'),

                                TextEntry::make('delivery.rider.vehicle_registration')
                                    ->label('Vehicle')
                                    ->default('N/A'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('delivery.estimated_delivery')
                                    ->label('Estimated Delivery')
                                    ->dateTime('M d, Y H:i')
                                    ->placeholder('Not set')
                                    ->default(null),

                                TextEntry::make('delivery.actual_delivery')
                                    ->label('Actual Delivery')
                                    ->dateTime('M d, Y H:i')
                                    ->placeholder('Pending')
                                    ->default(null),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->delivery)
                    ->collapsible(),

                Section::make('Notes & Additional Information')
                    ->schema([
                        TextEntry::make('notes')
                            ->columnSpanFull()
                            ->default('No additional notes'),

                        TextEntry::make('quotation.quotation_number')
                            ->label('Quotation Reference')
                            ->copyable(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Timeline')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime('M d, Y H:i'),

                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime('M d, Y H:i'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
