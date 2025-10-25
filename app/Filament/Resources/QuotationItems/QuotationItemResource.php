<?php

namespace App\Filament\Resources\QuotationItems;

use App\Filament\Resources\QuotationItems\Pages\ManageQuotationItems;
use App\Models\QuotationItem;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class QuotationItemResource extends Resource
{
    protected static ?string $model = QuotationItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Order & Quotations';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('quotation_id')
                    ->relationship('quotation', 'id')
                    ->required(),
                Select::make('prescription_item_id')
                    ->relationship('prescriptionItem', 'id')
                    ->required(),
                Select::make('supplier_id')
                    ->relationship('supplier', 'company_name')
                    ->required(),
                Select::make('supplier_medicine_id')
                    ->relationship('supplierMedicine', 'id')
                    ->required(),
                TextInput::make('quantity')
                    ->required()
                    ->numeric(),
                TextInput::make('unit_price')
                    ->required()
                    ->numeric(),
                TextInput::make('total_price')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->date()->label('Date')
                    ->sortable(),
                TextColumn::make('quotation.quotation_number')
                    ->searchable(),
             
                TextColumn::make('supplier.company_name')
                    ->searchable(),
               
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->numeric()->money('KES')
                    ->sortable(),
                TextColumn::make('total_price')
                    ->numeric()->money('KES')
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                // EditAction::make(),
                // DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageQuotationItems::route('/'),
        ];
    }
}
