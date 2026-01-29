<?php

namespace App\Filament\Operation\Resources\Orders\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                
                Section::make('Order Summary')
                    ->schema([
                        Group::make([
                            TextEntry::make('order_number')
                                ->label('Order Number')
                                ->size(TextSize::Small)
                                ->weight(FontWeight::Bold)
                                ->copyable()
                                ->icon('heroicon-m-clipboard-document'),
                            
                            TextEntry::make('status')
                                ->badge()
                                ->size(TextSize::Small)
                                ->color(fn (string $state): string => match ($state) {
                                    'pending_review' => 'warning',
                                    'sent_to_supplier' => 'info',
                                    'confirmed', 'processing', 'shipped' => 'primary',
                                    'delivered' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'gray',
                                })
                                ->icon(fn (string $state): string => match ($state) {
                                    'pending_review' => 'heroicon-m-clock',
                                    'sent_to_supplier' => 'heroicon-m-paper-airplane',
                                    'confirmed' => 'heroicon-m-check-circle',
                                    'processing' => 'heroicon-m-arrow-path',
                                    'shipped' => 'heroicon-m-truck',
                                    'delivered' => 'heroicon-m-check-badge',
                                    'cancelled' => 'heroicon-m-x-circle',
                                    default => 'heroicon-m-question-mark-circle',
                                })
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'pending_review' => 'Pending Review',
                                    'sent_to_supplier' => 'Sent to Supplier',
                                    'confirmed' => 'Confirmed',
                                    'processing' => 'Processing',
                                    'shipped' => 'Shipped',
                                    'delivered' => 'Delivered',
                                    'cancelled' => 'Cancelled',
                                    default => $state,
                                }),
                            
                            TextEntry::make('total_amount')
                                ->money('KES')
                                ->size(TextSize::Small)
                                ->weight(FontWeight::Bold)
                                ->color('success'),
                        ])->columns(3),
                    ]),

                Section::make('Financial Breakdown')
                    ->schema([
                        TextEntry::make('supplier_total')
                            ->label('Supplier Cost')
                            ->money('KES'),
                        
                        TextEntry::make('markup_total')
                            ->label('Markup')
                            ->money('KES')
                            ->color('warning'),
                        
                        TextEntry::make('total_amount')
                            ->label('Patient Total')
                            ->money('KES')
                            ->weight(FontWeight::Bold)
                            ->color('success'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('Related Information')
                    ->schema([
                        TextEntry::make('prescription.prescription_number')
                            ->label('Prescription')
                            // ->url(fn ($record) => $record->prescription 
                            //     ? route('filament.operations.resources.prescriptions.view', $record->prescription) 
                            //     : null)
                            ->color('primary')
                            ->icon('heroicon-m-document-text'),
                        
                        TextEntry::make('supplier.company_name')
                            ->label('Supplier')
                            ->icon('heroicon-m-building-storefront'),
                    
                        TextEntry::make('prescription.patient.full_name')
                            ->label('Patient')
                            ->icon('heroicon-m-user'),
                        
                      
                        
                        TextEntry::make('prescription.physician.name')
                            ->label('Physician')
                            ->icon('heroicon-m-user-circle'),
                    ])
                    ->columns(4)
                    ->collapsible(),

                Section::make('Important Dates')
                    ->schema([
                        TextEntry::make('ordered_at')
                            ->label('Order Created')
                            ->dateTime('M d, Y H:i A')
                            ->since()
                            ->icon('heroicon-m-calendar'),
                        
                        TextEntry::make('sent_to_supplier_at')
                            ->label('Sent to Supplier')
                            ->dateTime('M d, Y H:i A')
                            ->since()
                            ->placeholder('Not sent yet')
                            ->icon('heroicon-m-paper-airplane')
                            ->color(fn ($state) => $state ? 'success' : 'gray'),
                        
                       TextEntry::make('expected_delivery')
                            ->label('Expected Delivery')
                            ->dateTime('M d, Y H:i A')
                            ->icon('heroicon-m-truck')
                            ->color(fn ($record) => $record->is_overdue ? 'danger' : 'gray'),
                        
                       TextEntry::make('delivered_at')
                            ->label('Delivered')
                            ->dateTime('M d, Y H:i A')
                            ->since()
                            ->placeholder('Not delivered yet')
                            ->icon('heroicon-m-check-badge')
                            ->color(fn ($state) => $state ? 'success' : 'gray'),
                    ])
                    ->columns(4)
                    ->collapsible(),

               Section::make('Order Items')
                    ->schema([
                       RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                               TextEntry::make('medicine.generic_name')
                                    ->label('Medicine')
                                    ->weight(FontWeight::Bold),
                                
                               TextEntry::make('medicine.brand_name')
                                    ->label('Brand')
                                    ->placeholder('N/A'),
                                
                               TextEntry::make('quantity')
                                    ->label('Quantity')
                                    ->suffix(' units')
                                    ->badge()
                                    ->color('info'),
                                
                 
                                
                               TextEntry::make('unit_price')
                                    ->label('Unit Price')
                                    ->money('KES'),
                                
                               TextEntry::make('total_price')
                                    ->label('Subtotal')
                                    ->money('KES')
                                    ->weight(FontWeight::Bold)
                                    ->color('success'),
                            ])
                            ->columns(6)
                    ])
                    ->collapsible()->columnSpanFull(),

               Section::make('Delivery Information')
                    ->schema([
                       TextEntry::make('delivery.delivery_number')
                            ->label('Delivery Number')
                            // ->url(fn ($record) => $record->delivery 
                            //     ? route('filament.operations.resources.deliveries.view', $record->delivery) 
                            //     : null)
                            ->color('primary')
                            ->placeholder('No delivery assigned'),
                        
                       TextEntry::make('delivery.status')
                            ->label('Delivery Status')
                            ->badge()
                            ->placeholder('No delivery'),
                        
                       TextEntry::make('delivery.delivery_address')
                            ->label('Delivery Address')
                            ->placeholder('No delivery address')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->delivery)
                    ->collapsible(),
    
            ]);
    }
}
