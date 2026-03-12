<?php

namespace App\Filament\Physician\Resources\Physician\Prescriptions\Pages;

use App\Filament\Physician\Resources\Physician\Prescriptions\PrescriptionResource;
use App\Models\InsuranceProvider;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Split;
use Filament\Schemas\Schema;

class ViewPrescription extends ViewRecord
{
    protected static string $resource = PrescriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => $this->record->status === 'draft'),

            Action::make('submit')
                ->label('Submit Prescription')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Submit Prescription')
                ->modalDescription('Are you sure you want to submit this prescription? Once submitted, it will be processed for order.')
                ->visible(fn () => $this->record->status === 'draft')
                ->action(function () {
                    try {
                        $this->record->submit();
                        Notification::make()->success()->title('Prescription Submitted')
                            ->body('The prescription has been submitted successfully.')->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()->danger()->title('Submission Failed')->body($e->getMessage())->send();
                    }
                }),

            Action::make('cancel')
                ->label('Cancel Prescription')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel Prescription')
                ->form([
                    TextInput::make('reason')->label('Cancellation Reason')->required()->maxLength(500),
                ])
                ->visible(fn () => in_array($this->record->status, ['draft']))
                ->action(function (array $data) {
                    try {
                        $this->record->cancel($data['reason']);
                        Notification::make()->success()->title('Prescription Cancelled')
                            ->body('The prescription has been cancelled.')->send();
                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (\Exception $e) {
                        Notification::make()->danger()->title('Cancellation Failed')->body($e->getMessage())->send();
                    }
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([

                // ── ROW 1: Prescription meta (left, narrow) + Patient (right, wide) ──
                Section::make('Prescription')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextEntry::make('prescription_number')
                            ->label('Prescription #')
                            ->copyable()
                            ->icon('heroicon-o-clipboard-document')
                            ->weight('bold'),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft'       => 'gray',
                                'submitted'   => 'warning',
                                'processing'  => 'info',
                                'fulfilled'   => 'success',
                                'cancelled'   => 'danger',
                                default       => 'secondary',
                            }),

                        TextEntry::make('prescribed_at')
                            ->label('Prescribed')
                            ->dateTime('M d, Y H:i'),

                        TextEntry::make('total_amount')
                            ->label('Est. Total')
                            ->money('KES')
                            ->weight('bold')
                            ->color('success'),

                        TextEntry::make('items_count')
                            ->label('Medicines')
                            ->state(fn ($record) => $record->items->count())
                            ->suffix(' item(s)'),

                        TextEntry::make('quotation.status')
                            ->label('Quotation')
                            ->badge()
                            ->default('Not generated')
                            ->visible(fn ($record) => $record->status !== 'draft'),
                    ])
                    ->columns(2)
                    ->columnSpan(1),

                Section::make('Patient')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('patient.full_name')
                                ->label('Name')
                                ->weight('bold'),

                            TextEntry::make('patient.patient_number')
                                ->label('Patient #')
                                ->copyable(),

                            TextEntry::make('patient.phone')
                                ->label('Phone')
                                ->icon('heroicon-o-phone'),

                            TextEntry::make('patient.date_of_birth')
                                ->label('Date of Birth')
                                ->date('M d, Y')
                                ->suffix(fn ($record) => ' (' . $record->patient->date_of_birth->age . ' yrs)'),

                            TextEntry::make('patient.gender')
                                ->label('Gender')
                                ->badge(),

                            TextEntry::make('patient.insurance_provider')
                                ->label('Insurance')
                                ->icon('heroicon-o-shield-check')
                                ->formatStateUsing(function ($state) {
                                    if (is_numeric($state)) {
                                        $provider = InsuranceProvider::find($state);
                                        return $provider ? $provider->company_name : $state;
                                    }
                                    return $state;
                                }),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('patient.allergies')
                                ->label('Known Allergies')
                                ->default('None reported')
                                ->color('warning'),

                            TextEntry::make('patient.medical_conditions')
                                ->label('Medical Conditions')
                                ->default('None reported')
                                ->color('info'),
                        ]),
                    ])
                    ->collapsible()
                    ->columnSpan(2),

                // ── ROW 2: Clinical (left) + Timeline (right, compact) ──
                Section::make('Clinical Information')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        TextEntry::make('diagnosis')
                            ->label('Diagnosis')
                            ->default('Not specified')
                            ->columnSpanFull(),

                        TextEntry::make('notes')
                            ->label('Notes')
                            ->default('No additional notes')
                            ->columnSpanFull(),

                        IconEntry::make('insurance_covered')
                            ->label('Insurance Covered')
                            ->boolean(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->columnSpan(2),

                Section::make('Timeline')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        TextEntry::make('prescribed_at')
                            ->label('Prescribed')
                            ->dateTime('M d, Y H:i'),

                        TextEntry::make('expires_at')
                            ->label('Expires')
                            ->dateTime('M d, Y H:i')
                            ->formatStateUsing(fn ($state) => $state ? $state->format('M d, Y H:i') : 'N/A'),

                        TextEntry::make('fulfilled_at')
                            ->label('Fulfilled')
                            ->dateTime('M d, Y H:i')
                            ->formatStateUsing(fn ($state) => $state ? $state->format('M d, Y H:i') : 'N/A'),

                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('M d, Y H:i'),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->columnSpan(1),

                // ── ROW 3: Medicines full-width ──
                Section::make('Prescribed Medicines')
                    ->icon('heroicon-o-beaker')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                TextEntry::make('medicine.generic_name')
                                    ->label('Medicine')
                                    ->weight('bold')
                                    ->formatStateUsing(fn ($state, $record) =>
                                        $state . ($record->medicine->brand_name
                                            ? ' (' . $record->medicine->brand_name . ')'
                                            : '')
                                    ),

                                TextEntry::make('medicine.strength')
                                    ->label('Strength')
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('medicine.dosage_form')
                                    ->label('Form')
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('frequency')
                                    ->label('Frequency'),

                                TextEntry::make('duration_days')
                                    ->label('Duration')
                                    ->suffix(' days'),

                                TextEntry::make('quantity')
                                    ->label('Qty')
                                    ->suffix(' units'),

                                TextEntry::make('total_price')
                                    ->label('Cost')
                                    ->money('KES')
                                    ->weight('bold')
                                    ->color('success'),

                                TextEntry::make('dosage_instructions')
                                    ->label('Instructions')
                                    ->columnSpan(2)
                                    ->default('—'),
                            ])
                            ->columns(4),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

            ]);
    }
}