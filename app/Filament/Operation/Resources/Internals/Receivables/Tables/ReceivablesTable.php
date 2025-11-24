<?php

namespace App\Filament\Operation\Resources\Internals\Receivables\Tables;

use App\Models\Receivable;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReceivablesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('Ref#')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Click to copy'),

                TextColumn::make('prescription.prescription_number')
                    ->label('Prescription')
                    ->searchable()
                    ->sortable(),
                    // ->url(fn ($record) => $record->prescription_id ? route('filament.admin.resources.prescriptions.view', $record->prescription_id) : null),

                TextColumn::make('patient.name')
                    ->label('Patient')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('payment_source')
                    ->label('Payer')
                    ->colors([
                        'info' => 'insurance',
                        'success' => 'patient',
                    ])
                    ->icons([
                        'heroicon-o-building-office-2' => 'insurance',
                        'heroicon-o-user' => 'patient',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('insuranceProvider.company_name')
                    ->label('Insurance')
                    ->searchable()
                    ->sortable()
                    ->default('N/A')
                    ->toggleable(),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable()
                    ->weight('bold'),

                BadgeColumn::make('claim_status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'submitted',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'primary' => 'paid',
                    ])
                    ->icons([
                        'heroicon-o-pencil' => 'draft',
                        'heroicon-o-paper-airplane' => 'submitted',
                        'heroicon-o-check-badge' => 'approved',
                        'heroicon-o-x-circle' => 'rejected',
                        'heroicon-o-currency-dollar' => 'paid',
                    ])
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'Pending'),

                IconColumn::make('received_at')
                    ->label('Received')
                    ->boolean()
                    ->sortable()
                    ->tooltip(fn ($record) => $record->received_at ? 'Received on ' . $record->received_at->format('M d, Y') : 'Not received'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('payment_source')
                    ->label('Payer Type')
                    ->options([
                        'insurance' => 'Insurance',
                        'patient' => 'Patient',
                    ]),

                SelectFilter::make('claim_status')
                    ->label('Claim Status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'paid' => 'Paid',
                    ]),

                Filter::make('received')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('received_at'))
                    ->label('Payment Received'),

                Filter::make('pending')
                    ->query(fn (Builder $query): Builder => $query->whereNull('received_at'))
                    ->label('Pending Payment'),

                Filter::make('overdue_claims')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('payment_source', 'insurance')
                              ->where('claim_status', 'submitted')
                              ->where('claim_submitted_at', '<', now()->subDays(30))
                    )
                    ->label('Overdue Claims (30+ days)'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    
                    Action::make('submit_claim')
                        ->label('Submit to Insurance')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalDescription('Submit this claim to the insurance company for processing')
                        ->visible(fn (Receivable $record) => 
                            $record->payment_source === 'insurance' && 
                            $record->claim_status === 'draft'
                        )
                        ->form([
                            TextInput::make('claim_reference')
                                ->label('Claim Reference Number')
                                ->required()
                                ->helperText('Enter the claim ID from the insurance portal'),
                            DatePicker::make('claim_submitted_at')
                                ->label('Submission Date')
                                ->default(now())
                                ->required(),
                            Textarea::make('notes')
                                ->label('Submission Notes')
                                ->helperText('Any additional notes about this claim submission'),
                        ])
                        ->action(function (Receivable $record, array $data) {
                            $record->update([
                                'claim_status' => 'submitted',
                                'claim_reference' => $data['claim_reference'],
                                'claim_submitted_at' => $data['claim_submitted_at'],
                            ]);
                            
                            Notification::make()
                                ->success()
                                ->title('Claim Submitted')
                                ->body("Claim {$data['claim_reference']} submitted to {$record->insuranceCompany->name}")
                                ->send();
                        }),

                    Action::make('approve_claim')
                        ->label('Approve Claim')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription('Mark this claim as approved by the insurance company')
                        ->visible(fn (Receivable $record) => 
                            $record->payment_source === 'insurance' && 
                            $record->claim_status === 'submitted'
                        )
                        ->action(function (Receivable $record) {
                            $record->update(['claim_status' => 'approved']);
                            
                            Notification::make()
                                ->success()
                                ->title('Claim Approved')
                                ->body('Awaiting payment from insurance company')
                                ->send();
                        }),

                    Action::make('mark_received')
                        ->label('Record Payment')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription('Record that payment has been received')
                        ->visible(fn (Receivable $record) => !$record->received_at)
                        ->form([
                            DateTimePicker::make('received_at')
                                ->label('Payment Received On')
                                ->default(now())
                                ->required(),
                            TextInput::make('transaction_reference')
                                ->label('Transaction/Transfer Reference')
                                ->helperText('Bank reference or transaction ID'),
                            Textarea::make('notes')
                                ->label('Payment Notes'),
                        ])
                        ->action(function (Receivable $record, array $data) {
                            $record->update([
                                'received_at' => $data['received_at'],
                            ]);
                            
                            // Update claim status to paid if insurance
                            if ($record->payment_source === 'insurance') {
                                $record->update(['claim_status' => 'paid']);
                            }
                            
                            // Create transaction record
                            $record->transaction()->create([
                                'reference' => 'TXN-' . strtoupper(uniqid()),
                                'amount' => $record->amount,
                                'currency' => 'KES',
                                'type' => 'receivable',
                                'status' => 'completed',
                                'completed_at' => $data['received_at'],
                                'notes' => $data['notes'] ?? null,
                            ]);
                            
                            Notification::make()
                                ->success()
                                ->title('Payment Recorded')
                                ->body("KES " . number_format($record->amount, 2) . " received from " . ($record->payment_source === 'insurance' ? $record->insuranceCompany->name : $record->patient->name))
                                ->send();
                        }),

                    Action::make('reject_claim')
                        ->label('Reject Claim')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription('Mark this claim as rejected by the insurance company')
                        ->visible(fn (Receivable $record) => 
                            $record->payment_source === 'insurance' && 
                            in_array($record->claim_status, ['submitted', 'approved'])
                        )
                        ->form([
                            Textarea::make('rejection_reason')
                                ->label('Rejection Reason')
                                ->required()
                                ->helperText('Why was this claim rejected?'),
                        ])
                        ->action(function (Receivable $record, array $data) {
                            $record->update(['claim_status' => 'rejected']);
                            
                            Notification::make()
                                ->danger()
                                ->title('Claim Rejected')
                                ->body('Review and resubmit if necessary')
                                ->send();
                        }),

                    Action::make('resubmit_claim')
                        ->label('Resubmit Claim')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (Receivable $record) => 
                            $record->payment_source === 'insurance' && 
                            $record->claim_status === 'rejected'
                        )
                        ->action(function (Receivable $record) {
                            $record->update([
                                'claim_status' => 'draft',
                                'claim_reference' => null,
                            ]);
                            
                            Notification::make()
                                ->info()
                                ->title('Ready for Resubmission')
                                ->body('Update claim details and submit again')
                                ->send();
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
