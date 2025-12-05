<?php

namespace App\Filament\Physician\Resources\Physician\ClaimForms\Pages;

use App\Filament\Physician\Resources\Physician\ClaimForms\ClaimFormResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewClaimForm extends ViewRecord
{
    protected static string $resource = ClaimFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => $this->record->status === 'draft'),

            Action::make('downloadPDF')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    if ($this->record->document_id) {
                        $document = $this->record->document;
                        return \Storage::download($document->file_path, $document->file_name);
                    }
                })
                ->visible(fn () => $this->record->document_id !== null),

            Action::make('submit')
                ->label('Submit')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->submit();
                    
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Claim form submitted')
                        ->send();
                })
                ->visible(fn () => $this->record->status === 'draft'),

            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->approve();
                    
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Claim form approved')
                        ->send();
                })
                ->visible(fn () => 
                    $this->record->status === 'submitted' && 
                    in_array(auth()->user()->role->name, ['admin', 'operations', 'insurer'])
                ),

            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->reject();
                    
                    \Filament\Notifications\Notification::make()
                        ->warning()
                        ->title('Claim form rejected')
                        ->send();
                })
                ->visible(fn () => 
                    $this->record->status === 'submitted' && 
                    in_array(auth()->user()->role->name, ['admin', 'operations', 'insurer'])
                ),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Claim Form Details')
                    ->schema([
                        TextEntry::make('form_number')
                            ->label('Form Number')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'draft' => 'secondary',
                                'submitted' => 'warning',
                                'processing' => 'info',
                                'approved' => 'success',
                                'rejected' => 'danger',
                            }),
                        TextEntry::make('submission_type')
                            ->label('Submission Type')
                            ->badge()
                            ->formatStateUsing(fn ($state) => ucfirst($state)),
                        TextEntry::make('submitted_at')
                            ->label('Submitted At')
                            ->dateTime()
                            ->visible(fn ($record) => $record->submitted_at),
                    ])
                    ->columns(2),

                Section::make('Related Information')
                    ->schema([
                        TextEntry::make('prescription.prescription_number')
                            ->label('Prescription'),
                            // ->url(fn ($record) => route('filament.admin.resources.prescriptions.view', $record->prescription_id)),
                        TextEntry::make('patient.patient_number')
                            ->label('Patient')
                            ->formatStateUsing(fn ($record) => 
                                "{$record->patient->patient_number} - {$record->patient->first_name} {$record->patient->last_name}"
                            ),
                        TextEntry::make('insuranceProvider.company_name')
                            ->label('Insurance Provider'),
                        TextEntry::make('physician.name')
                            ->label('Physician'),
                    ])
                    ->columns(2),

                Section::make('Clinical Information')
                    ->schema([
                        TextEntry::make('diagnosis')
                            ->label('Diagnosis')
                            ->columnSpanFull(),
                        TextEntry::make('treatment_notes')
                            ->label('Treatment Notes')
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record->treatment_notes),
                    ]),

                Section::make('Additional Fields')
                    ->schema([
                        KeyValueEntry::make('form_data')
                            ->label('Custom Fields')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->form_data))
                    ->collapsible(),

                Section::make('Digital Signatures')
                    ->schema([
                        TextEntry::make('physician_signature')
                            ->label('Physician Signature')
                            ->visible(fn ($record) => $record->physician_signature),
                        TextEntry::make('patient_signature')
                            ->label('Patient Signature')
                            ->visible(fn ($record) => $record->patient_signature),
                        TextEntry::make('signed_at')
                            ->label('Signed At')
                            ->dateTime()
                            ->visible(fn ($record) => $record->signed_at),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->submission_type === 'online')
                    ->collapsible(),

                Section::make('Attached Document')
                    ->schema([
                        TextEntry::make('document.document_number')
                            ->label('Document Number'),
                            // ->url(fn ($record) => 
                            //     $record->document_id 
                            //         ? route('filament.admin.resources.documents.view', $record->document_id) 
                            //         : null
                            // ),
                        TextEntry::make('document.file_name')
                            ->label('File Name'),
                        ImageEntry::make('document.file_path')
                            ->label('Preview')
                            ->visible(fn ($record) => $record->document?->isImage())
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->document_id)
                    ->collapsible(),
            ]);
    }
}
