<?php

namespace App\Filament\Operation\Resources\Medicines\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Services\PricingService;

class MedicinesTable
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
                    ->label('Price')
                    ->placeholder('--')
                    ->money('KES')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        $supplierPrice = $record->getCheapestSupplierPrice(1);

                        if (!$supplierPrice) return null;

                        $pricing = app(PricingService::class)
                            ->calculateFinalPrice((float) $supplierPrice, $record, 1);

                        return $pricing['final_unit_price'];
                    })
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
                    ->modalContent(function ($record) {
                        $pricingService = app(PricingService::class);
                        $suppliers = $record->getAvailableSuppliers(1);
                        $suppliersWithPricing = $suppliers->map(function($supplier) use($pricingService, $record) {
                            $pricing = $pricingService->calculateFinalPrice($supplier->unit_price, $record, 1);
                           $supplier->pricing = $pricing;
                           return $supplier;
                        });

                        return view('filament.insurer.resources.price-catalogue.view-suppliers', [
                            'medicine' => $record,
                            'suppliers' => $suppliersWithPricing,
                        ]);



                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])->defaultSort('generic_name', 'asc')
            ->poll('30s'); ;
    }
}
