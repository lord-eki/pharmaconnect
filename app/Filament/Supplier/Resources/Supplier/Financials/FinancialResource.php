<?php

namespace App\Filament\Supplier\Resources\Supplier\Financials;

use App\Filament\Supplier\Resources\Supplier\Financials\Pages\CreateFinancial;
use App\Filament\Supplier\Resources\Supplier\Financials\Pages\EditFinancial;
use App\Filament\Supplier\Resources\Supplier\Financials\Pages\ListFinancials;
use App\Filament\Supplier\Resources\Supplier\Financials\Schemas\FinancialForm;
use App\Filament\Supplier\Resources\Supplier\Financials\Tables\FinancialsTable;
use App\Models\Payment;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FinancialResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationLabel = 'Financial Tracking';

    protected static ?string $modelLabel = 'Payment';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function getEloquentQuery(): Builder
    {
        $supplierId = Auth::user()->userProfile->id ?? null;

        return parent::getEloquentQuery()
            ->where('payee_id', $supplierId)
            ->with(['order.prescription.patient', 'order.prescription.physician']);
    }

    public static function form(Schema $schema): Schema
    {
        return FinancialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinancialsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFinancials::route('/'),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('payment_reference')
                                    ->label('Payment Reference')
                                    ->badge()
                                    ->color('primary')
                                    ->copyable(),

                                TextEntry::make('amount')
                                    ->label('Amount')
                                    ->money('KES')
                                    ->size('lg')
                                    ->weight('bold'),

                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'completed' => 'success',
                                        'failed' => 'danger',
                                        'refunded' => 'gray',
                                        default => 'gray',
                                    }),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('payment_method')
                                    ->label('Payment Method')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'mpesa' => 'M-Pesa',
                                        'card' => 'Card Payment',
                                        'bank_transfer' => 'Bank Transfer',
                                        'cash' => 'Cash',
                                        'insurance' => 'Insurance',
                                        default => $state,
                                    }),

                                TextEntry::make('created_at')
                                    ->label('Payment Date')
                                    ->dateTime(),

                                TextEntry::make('processed_at')
                                    ->label('Processed At')
                                    ->dateTime()
                                    ->placeholder('Not processed yet'),
                            ]),

                        TextEntry::make('gateway_reference')
                            ->label('Gateway Reference')
                            ->placeholder('N/A')
                            ->copyable(),
                    ]),

                Section::make('Order Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('order.order_number')
                                    ->label('Order Number')
                                    ->badge()
                                    ->color('primary'),

                                TextEntry::make('order.prescription.physician.name')
                                    ->label('Physician')
                                    ->icon('heroicon-o-user'),

                                TextEntry::make('order.prescription.patient.first_name')
                                    ->label('Patient')
                                    ->formatStateUsing(fn ($record) => 
                                        $record->order?->prescription?->patient 
                                            ? "{$record->order->prescription->patient->first_name} {$record->order->prescription->patient->last_name}"
                                            : 'N/A'
                                    )
                                    ->icon('heroicon-o-identification'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Additional Information')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Payment Notes')
                            ->placeholder('No notes')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $supplierId = Auth::user()->userProfile->id ?? null;
        
        $pending = Payment::where('payee_id', $supplierId)
            ->where('status', 'pending')
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
}
