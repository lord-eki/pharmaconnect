<?php

namespace App\Filament\Supplier\Resources\Supplier\Quotations;

use App\Filament\Supplier\Resources\Supplier\Quotations\Pages\CreateQuotation;
use App\Filament\Supplier\Resources\Supplier\Quotations\Pages\EditQuotation;
use App\Filament\Supplier\Resources\Supplier\Quotations\Pages\ListQuotations;
use App\Filament\Supplier\Resources\Supplier\Quotations\Schemas\QuotationForm;
use App\Filament\Supplier\Resources\Supplier\Quotations\Tables\QuotationsTable;
use App\Models\Quotation;
use BackedEnum;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;


    public static function getEloquentQuery(): Builder
    {
        $supplierId = Auth::user()->userProfile->id ?? null;
        
        return parent::getEloquentQuery()
            ->whereHas('items', function ($query) use ($supplierId) {
                $query->where('supplier_id', $supplierId);
            })
            ->with(['prescription.patient', 'prescription.physician', 'items']);
    }

    public static function form(Schema $schema): Schema
    {
        return QuotationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuotationsTable::configure($table);
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
            'index' => ListQuotations::route('/'),
            'view' => Pages\ViewQuotation::route('/{record}'),
            'edit' => EditQuotation::route('/{record}/edit'),
        ];
    }



    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quotation Overview')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('quotation_number')
                                    ->label('Quotation Number')
                                    ->badge()
                                    ->color('primary'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'sent' => 'info',
                                        'accepted' => 'success',
                                        'rejected' => 'danger',
                                        'expired' => 'gray',
                                        default => 'gray',
                                    }),

                                TextEntry::make('total_amount')
                                    ->label('Total Amount')
                                    ->money('KES')
                                    ->size('lg')
                                    ->weight('bold'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Requested On')
                                    ->dateTime(),

                                TextEntry::make('valid_until')
                                    ->label('Valid Until')
                                    ->dateTime()
                                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'success'),

                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->since(),
                            ]),
                    ]),

                Section::make('Prescription Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('prescription.prescription_number')
                                    ->label('Prescription Number')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('prescription.physician.name')
                                    ->label('Prescribing Physician')
                                    ->icon('heroicon-o-user'),

                                TextEntry::make('prescription.patient.first_name')
                                    ->label('Patient')
                                    ->formatStateUsing(fn ($record) => 
                                        $record->prescription->patient 
                                            ? "{$record->prescription->patient->first_name} {$record->prescription->patient->last_name}"
                                            : 'N/A'
                                    )
                                    ->icon('heroicon-o-identification'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Quotation Items')
                    ->schema([
                        RepeatableEntry::make('quotationItems')
                            ->label('')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('supplierMedicine.medicine.generic_name')
                                            ->label('Medicine')
                                            ->formatStateUsing(fn ($record) => 
                                                $record->supplierMedicine?->medicine 
                                                    ? "{$record->supplierMedicine->medicine->generic_name} - {$record->supplierMedicine->medicine->brand_name} ({$record->supplierMedicine->medicine->strength})"
                                                    : 'N/A'
                                            )
                                            ->columnSpan(2),

                                        TextEntry::make('quantity')
                                            ->label('Quantity')
                                            ->numeric(),

                                        TextEntry::make('unit_price')
                                            ->label('Unit Price')
                                            ->money('KES'),
                                    ]),

                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('total_price')
                                            ->label('Total Price')
                                            ->money('KES')
                                            ->size('lg')
                                            ->weight('bold'),

                                        IconEntry::make('available')
                                            ->label('Available?')
                                            ->boolean(),

                                        TextEntry::make('notes')
                                            ->label('Notes')
                                            ->placeholder('No notes')
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->columns(1)
                            ->contained(false),
                    ]),

                Section::make('Additional Notes')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('')
                            ->placeholder('No additional notes')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
