<?php

namespace App\Filament\Resources\Quotations\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('prescription_item_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('prescription_item_id')
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime(),
                TextColumn::make('prescriptionItem.medicine.generic_name')
                    ->label('Medicine')
                    ->searchable(['generic_name', 'brand_name'])
                    ->sortable()
                    ->description(fn ($record) => $record->prescriptionItem?->medicine?->brand_name
                        ? "Brand: {$record->prescriptionItem->medicine->brand_name}"
                        : null),

                TextColumn::make('supplierMedicine.supplier.company_name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->supplierMedicine?->medicine?->generic_name
                        ? "Medicine: {$record->supplierMedicine->medicine->generic_name}"
                        : null),
                TextColumn::make('quantity'),
                TextColumn::make('unit_price')->money('KES'),
                TextColumn::make('total_price')->money('KES'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
