<?php

namespace App\Filament\Physician\Resources\Physician\Patients\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PatientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient_number')
                    ->label('Patient #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->description(fn ($record) => $record->phone),

                TextColumn::make('age')
                    ->label('Age')
                    ->suffix(' yrs')
                    ->alignCenter(),

                TextColumn::make('gender')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'male' => 'info',
                        'female' => 'success',
                    }),

                IconColumn::make('insurance_provider')
                    ->label('Insurance')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('prescriptions_count')
                    ->counts('prescriptions')
                    ->label('Prescriptions')
                    ->badge()
                    ->color('primary'),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Registered')
                    ->date('M j, Y')
                    ->sortable()

            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All patients')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                SelectFilter::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                    ])
                    ->multiple(),

                Filter::make('has_insurance')
                    ->label('Has Insurance')
                    ->query(fn ($query) => $query->whereNotNull('insurance_number')),

                Filter::make('has_allergies')
                    ->label('Has Allergies')
                    ->query(fn ($query) => $query->whereNotNull('allergies')),

            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('create_prescription')
                        ->label('New Prescription')
                        ->icon('heroicon-o-document-plus')
                        ->color('success'),
                    // ->url(fn ($record) => route('filament.physician.resources.prescriptions.create', [
                    //     'patient_id' => $record->id,
                    // ])),
                ])->label('More actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size(Size::Small)
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->can('delete_patient')),
                ]),
            ])->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->where('physician_id', Auth::id())
            );
    }
}
