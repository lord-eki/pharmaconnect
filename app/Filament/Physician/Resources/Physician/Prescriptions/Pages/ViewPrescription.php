<?php

namespace App\Filament\Physician\Resources\Physician\Prescriptions\Pages;

use App\Filament\Physician\Resources\Physician\Prescriptions\PrescriptionResource;
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

                        Notification::make()
                            ->success()
                            ->title('Prescription Submitted')
                            ->body('The prescription has been submitted and quotations are being generated.')
                            ->send();

                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Submission Failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('cancel')
                ->label('Cancel Prescription')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel Prescription')
                ->form([
                    TextInput::make('reason')
                        ->label('Cancellation Reason')
                        ->required()
                        ->maxLength(500),
                ])
                ->visible(fn () => in_array($this->record->status, ['draft']))
                ->action(function (array $data) {
                    try {
                        $this->record->cancel($data['reason']);

                        Notification::make()
                            ->success()
                            ->title('Prescription Cancelled')
                            ->body('The prescription has been cancelled.')
                            ->send();

                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Cancellation Failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            // Action::make('print')
            //     ->label('Print')
            //     ->icon('heroicon-o-printer')
            //     ->color('gray')
            //     // ->url(fn () => route('prescriptions.print', $this->record))
            //     ->openUrlInNewTab(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Prescription Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('prescription_number')
                                    ->label('Prescription #')
                                    ->copyable()
                                    ->icon('heroicon-o-clipboard-document'),

                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'draft' => 'gray',
                                        'submitted' => 'warning',
                                        'processing' => 'info',
                                        'fulfilled' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'secondary',
                                    }),

                                TextEntry::make('prescribed_at')
                                    ->label('Date Prescribed')
                                    ->dateTime('M d, Y H:i'),
                            ]),
                    ]),

                Section::make('Patient Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('patient.full_name')
                                    ->label('Patient Name'),

                                TextEntry::make('patient.patient_number')
                                    ->label('Patient Number')
                                    ->copyable(),

                                TextEntry::make('patient.phone')
                                    ->label('Phone')
                                    ->icon('heroicon-o-phone'),

                                TextEntry::make('patient.date_of_birth')
                                    ->label('Date of Birth')
                                    ->date('M d, Y')
                                    ->suffix(fn ($record) => ' ('.$record->patient->date_of_birth->age.' years)'),

                                TextEntry::make('patient.gender')
                                    ->label('Gender')
                                    ->badge(),

                                TextEntry::make('patient.insurance_provider')
                                    ->label('Insurance')
                                    ->default('None')
                                    ->icon('heroicon-o-shield-check'),
                            ]),

                        Grid::make(2)
                            ->schema([
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
                    ->collapsible(),

                Section::make('Clinical Information')
                    ->schema([
                        TextEntry::make('diagnosis')
                            ->columnSpanFull()
                            ->default('Not specified'),

                        TextEntry::make('notes')
                            ->columnSpanFull()
                            ->default('No additional notes'),

                        IconEntry::make('insurance_covered')
                            ->label('Insurance Coverage')
                            ->boolean(),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Section::make('Prescribed Medicines')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('medicine.generic_name')
                                            ->label('Medicine')
                                            ->weight('bold')
                                            ->columnSpan(2),

                                        TextEntry::make('quantity')
                                            ->suffix(' units'),

                                        TextEntry::make('total_price')
                                            ->label('Est. Cost')
                                            ->money('KES'),
                                    ]),

                                Grid::make([
                                    TextEntry::make('medicine.strength')
                                        ->label('Strength')
                                        ->badge()
                                        ->color('gray'),

                                    TextEntry::make('medicine.dosage_form')
                                        ->label('Form')
                                        ->badge()
                                        ->color('gray'),

                                    TextEntry::make('dosage_instructions')
                                        ->label('Dosage Instructions')
                                        ->columnSpanFull()
                                        ->icon('heroicon-o-information-circle'),
                                ]),

                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('frequency')
                                            ->label('Frequency'),

                                        TextEntry::make('duration_days')
                                            ->label('Duration')
                                            ->suffix(' days'),
                                        TextEntry::make('notes')
                                            ->label('Special Instructions')
                                            ->columnSpanFull()
                                            ->default('None')
                                            ->color('warning'),
                                    ]),

                            ])
                            ->contained(true),
                    ])->collapsible(),

                Section::make('Financial Summary')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('total_amount')
                                    ->label('Estimated Total')
                                    ->money('KES')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('success'),

                                TextEntry::make('items_count')
                                    ->label('Total Items')
                                    ->state(fn ($record) => $record->items->count())
                                    ->suffix(' medicine(s)'),

                                TextEntry::make('quotation.status')
                                    ->label('Quotation Status')
                                    ->badge()
                                    ->default('Not generated')
                                    ->visible(fn ($record) => $record->status !== 'draft'),
                            ]),
                    ]),

                Section::make('Timeline')
                    ->schema([
                        Grid::make(4)
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
                                    ->formatStateUsing(fn ($state) => $state ? $state->format('M d, Y H:i') : 'N/A')
                                    ->visible(fn ($record) => $record->fulfilled_at),

                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime('M d, Y H:i'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
