<?php

namespace App\Filament\Physician\Resources\Physician\Commissions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                        TextInput::make('prescription.prescription_number')
                            ->label('Prescription Number')
                            ->disabled(),
                            
                        TextInput::make('order.order_number')
                            ->label('Order Number')
                            ->disabled(),
                            
                        TextInput::make('commission_rate')
                            ->label('Commission Rate (%)')
                            ->disabled()
                            ->suffix('%'),
                            
                        TextInput::make('gross_amount')
                            ->label('Gross Amount')
                            ->disabled()
                            ->prefix('KES'),
                            
                        TextInput::make('commission_amount')
                            ->label('Commission Amount')
                            ->disabled()
                            ->prefix('KES'),
                            
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'paid' => 'Paid',
                            ])
                            ->disabled(),
                            
                        DateTimePicker::make('approved_at')
                            ->label('Approved Date')
                            ->disabled(),
                            
                        DateTimePicker::make('paid_at')
                            ->label('Payment Date')
                            ->disabled(),
                            
                        TextInput::make('payment_reference')
                            ->label('Payment Reference')
                            ->disabled()
                            ->visible(fn ($record) => !empty($record?->payment_reference)),
            ]);
    }

    
}
