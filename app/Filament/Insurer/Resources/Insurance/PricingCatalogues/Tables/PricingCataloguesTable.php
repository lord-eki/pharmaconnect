<?php

namespace App\Filament\Insurer\Resources\Insurance\PricingCatalogues\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PricingCataloguesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('generic_name')
                    ->label('Generic Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('brand_name')
                    ->label('Brand Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('strength')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('dosage_form')
                    ->label('Form')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),

                TextColumn::make('cheapest_price')
                    ->label('Price')->placeholder('--')
                    ->money('KES')
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record->getCheapestSupplierPrice(1))
                    ->color('success'),

                IconColumn::make('has_stock')
                    ->label('In Stock')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->hasStock())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('manufacturer')
                    ->searchable()
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->preload(),

                SelectFilter::make('dosage_form')
                    ->options([
                        'Tablet' => 'Tablet',
                        'Capsule' => 'Capsule',
                        'Syrup' => 'Syrup',
                        'Injection' => 'Injection',
                        'Cream' => 'Cream',
                        'Ointment' => 'Ointment',
                        'Drops' => 'Drops',
                        'Inhaler' => 'Inhaler',
                    ]),

                Filter::make('in_stock')
                    ->query(fn (Builder $query) => $query->withStock(1))
                    ->label('In Stock Only'),

                Filter::make('prescription_required')
                    ->query(fn (Builder $query) => $query->where('prescription_required', true))
                    ->label('Prescription Required'),

            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading(fn ($record) => $record->display_name)
                    ->modalContent(fn ($record) => view('filament.insurer.resources.price-catalogue.view-suppliers', [
                        'medicine' => $record,
                        'suppliers' => $record->getAvailableSuppliers(1),
                    ])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])->defaultSort('generic_name', 'asc')
            ->poll('30s'); ;
    }
}
