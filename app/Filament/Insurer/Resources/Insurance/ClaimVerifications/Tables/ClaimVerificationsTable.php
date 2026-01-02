<?php

namespace App\Filament\Insurer\Resources\Insurance\ClaimVerifications\Tables;

use App\Models\InsuranceClaim;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClaimVerificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('claim_number')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('policy_number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('prescription.prescription_number')
                    ->label('Prescription #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->action(
                        Action::make('viewPrescription')
                            ->modalHeading(fn ($record) => 'Prescription: '.($record->prescription?->prescription_number ?? 'N/A'))
                            ->modalContent(fn ($record) => view('filament.insurer.modals.view-prescription', [
                                'prescription' => $record->prescription?->load(['items.medicine', 'patient', 'physician']),
                            ]))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close')
                            ->icon('heroicon-o-document-text')
                            ->visible(fn ($record) => $record->prescription !== null)
                    ),

                TextColumn::make('claimed_amount')
                    ->label('Claimed')
                    ->money('KES')
                    ->sortable(),

                TextColumn::make('approved_amount')
                    ->label('Approved')
                    ->money('KES')
                    ->sortable()
                    ->placeholder('—'),

                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'submitted',
                        'info' => 'under_review',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'primary' => 'paid',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'submitted',
                        'heroicon-o-eye' => 'under_review',
                        'heroicon-o-check-circle' => 'approved',
                        'heroicon-o-x-circle' => 'rejected',
                        'heroicon-o-currency-dollar' => 'paid',
                    ]),

                TextColumn::make('reviewed_at')
                    ->label('Reviewed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'submitted' => 'Submitted',
                        'under_review' => 'Under Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'paid' => 'Paid',
                    ]),

                Filter::make('submitted_today')
                    ->query(fn (Builder $query) => $query->whereDate('submitted_at', today()))
                    ->label('Submitted Today'),

                Filter::make('pending_review')
                    ->query(fn (Builder $query) => $query->whereIn('status', ['submitted', 'under_review']))
                    ->label('Pending Review'),
            ])
            ->recordActions([
                ActionGroup::make([
                    // ViewAction::make(),

                    // EditAction::make()
                    //     ->visible(fn ($record) => $record->canBeApproved() || $record->canBeRejected()),

                    Action::make('approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->canBeApproved())
                        ->requiresConfirmation()
                        ->form([

                            TextInput::make('approved_amount')
                                ->label('Approved Amount')
                                ->prefix('KES')
                                ->numeric()
                                ->required()
                                ->default(fn ($record) => $record->claimed_amount),

                            Textarea::make('notes')
                                ->label('Review Notes')
                                ->rows(3),
                        ])
                        ->action(function (InsuranceClaim $record, array $data) {
                            $record->approve($data['approved_amount'], $data['notes'] ?? null);

                            Notification::make()
                                ->title('Claim Approved')
                                ->success()
                                ->send();
                        }),

                    Action::make('reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => $record->canBeRejected())
                        ->requiresConfirmation()
                        ->form([
                            Textarea::make('rejection_reason')
                                ->label('Rejection Reason')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (InsuranceClaim $record, array $data) {
                            $record->reject($data['rejection_reason']);

                            Notification::make()
                                ->title('Claim Rejected')
                                ->warning()
                                ->send();
                        }),

                    // Action::make('mark_paid')
                    //     ->icon('heroicon-o-currency-dollar')
                    //     ->color('primary')
                    //     ->visible(fn ($record) => $record->status === 'approved')
                    //     ->requiresConfirmation()
                    //     ->action(function (InsuranceClaim $record) {
                    //         $record->markAsPaid();

                    //         Notification::make()
                    //             ->title('Claim Marked as Paid')
                    //             ->success()
                    //             ->send();
                    //     }),
                ]),
            ])
            ->toolbarActions([
                BulkAction::make('move_to_review')
                    ->label('Move to Review')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            if ($record->status === 'submitted') {
                                $record->submitForReview();
                            }
                        }

                        Notification::make()
                            ->title('Claims moved to review')
                            ->success()
                            ->send();
                    }),
            ])->defaultSort('submitted_at', 'desc');
    }
}
