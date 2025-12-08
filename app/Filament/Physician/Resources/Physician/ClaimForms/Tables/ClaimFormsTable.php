<?php

namespace App\Filament\Physician\Resources\Physician\ClaimForms\Tables;

use App\Models\ClaimForm;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClaimFormsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('form_number')
                    ->label('Form #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('prescription.prescription_number')
                    ->label('Prescription')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('patient.patient_number')
                    ->label('Patient')
                    ->searchable()
                    ->formatStateUsing(fn ($record) => 
                        "{$record->patient->patient_number} - {$record->patient->first_name} {$record->patient->last_name}"
                    ),

                TextColumn::make('insuranceProvider.company_name')
                    ->label('Insurance Provider')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('physician.name')
                    ->label('Physician')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('submission_type')
                    ->label('Type')
                    ->colors([
                        'success' => 'online',
                        'warning' => 'manual',
                    ])
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'submitted',
                        'info' => 'processing',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->sortable(),

                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('document_id')
                    ->label('Has Document')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'processing' => 'Processing',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                SelectFilter::make('submission_type')
                    ->options([
                        'online' => 'Online',
                        'manual' => 'Manual',
                    ]),

                SelectFilter::make('insurance_provider_id')
                    ->label('Insurance Provider')
                    ->relationship('insuranceProvider', 'company_name'),

                Filter::make('submitted')
                    ->query(fn ($query) => $query->whereNotNull('submitted_at'))
                    ->label('Submitted Only'),

                Filter::make('my_forms')
                    ->query(fn ($query) => $query->where('physician_id', auth()->id()))
                    ->label('My Forms')
                    ->default(),
            ])
            ->recordActions([
              ViewAction::make(),

                Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (ClaimForm $record) {
                        $record->submit();
                        
                        Notification::make()
                            ->success()
                            ->title('Claim form submitted')
                            ->body('The claim form has been submitted for processing.')
                            ->send();
                    })
                    ->visible(fn (ClaimForm $record) => $record->status === 'draft'),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (ClaimForm $record) {
                        $record->approve();
                        
                        Notification::make()
                            ->success()
                            ->title('Claim form approved')
                            ->send();
                    })
                    ->visible(fn (ClaimForm $record) => 
                        $record->status === 'submitted' && 
                        auth()->user()->hasAnyRole(['Admin', 'Operation', 'Insurer'])
                    ),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (ClaimForm $record) {
                        $record->reject();
                        
                        Notification::make()
                            ->warning()
                            ->title('Claim form rejected')
                            ->send();
                    })
                    ->visible(fn (ClaimForm $record) => 
                        $record->status === 'submitted' && 
                        auth()->user()->hasAnyRole( ['Admin', 'Operation', 'Insurer'])
                    ),

                Action::make('viewDocument')
                    ->label('View Document')
                    ->icon('heroicon-o-document-text')
                    // ->url(fn (ClaimForm $record) => 
                    //     $record->document_id 
                    //         ? route('filament.admin.resources.documents.view', $record->document_id) 
                    //         : null
                    // )
                    ->visible(fn (ClaimForm $record) => $record->document_id !== null),

                EditAction::make()
                    ->visible(fn (ClaimForm $record) => $record->status === 'draft'),

                DeleteAction::make()
                    ->visible(fn (ClaimForm $record) => $record->status === 'draft'),
            ])
            ->toolbarActions([
           BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    BulkAction::make('bulkSubmit')
                        ->label('Submit Selected')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->status === 'draft') {
                                    $record->submit();
                                }
                            }
                            
                            Notification::make()
                                ->success()
                                ->title('Claim forms submitted')
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
            
    }
}
