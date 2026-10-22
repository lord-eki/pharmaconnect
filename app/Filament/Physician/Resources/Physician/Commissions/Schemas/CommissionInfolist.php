<?php

namespace App\Filament\Physician\Resources\Physician\Commissions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class CommissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Commission Summary')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('created_at')->label('Date Earned')->date('M d, Y'),
                    TextEntry::make('prescription.patient.full_name')->label('Patient')->placeholder('—'),
                    TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn ($state) => ucfirst($state))
                        ->color(fn (string $state) => match ($state) {
                            'pending'  => 'warning',
                            'approved' => 'info',
                            'paid'     => 'success',
                            default    => 'gray',
                        }),
                ]),
            ]),

            Section::make('Earnings')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('gross_amount')->label('Order Amount')->money('KES'),
                    TextEntry::make('commission_rate')->label('Rate')->suffix('%'),
                    TextEntry::make('commission_amount')
                        ->label('Commission Earned')
                        ->money('KES')
                        ->weight(FontWeight::Bold)
                        ->size(TextSize::Large)
                        ->color('success'),
                ]),
            ]),

            Section::make('Payment Timeline')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('approved_at')->label('Approved')->dateTime('M d, Y H:i')->placeholder('Awaiting approval'),
                    TextEntry::make('paid_at')->label('Paid')->dateTime('M d, Y H:i')->placeholder('Not yet paid'),
                    TextEntry::make('payment_reference')->label('Payment Reference')->placeholder('—')->copyable(),
                ]),
            ]),
        ]);
    }
}