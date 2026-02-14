<?php

namespace App\Filament\Resources\Prescriptions\RelationManagers;

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
use Filament\Schemas\View\Components\TextComponent;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('dose_amount')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('dose_amount')
            ->columns([
                TextColumn::make('created_at'),
                TextColumn::make('prescription.prescription_number'),
                TextColumn::make('medicine.generic_name'),
                TextColumn::make('dose_amount'),
                TextColumn::make('dosage_instructions'),
                TextColumn::make('frequency'),
                TextColumn::make('duration_days'),
                TextColumn::make('status')->badge()->color('gray'),
                TextColumn::make('total_volume_required'),
                TextColumn::make('unit_of_measurement'),
                TextColumn::make('volume_per_unit')
            ])
            ->filters([
                //
            ])
            ->headerActions([
           
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
