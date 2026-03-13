<?php

namespace App\Filament\Operation\Resources\InsuranceClaims\Tables;

use App\Models\InsuranceClaim;
use App\Services\InsuranceClaimPDFService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InsuranceClaimsTable
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
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->orWhereHas('patient', function (Builder $q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
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

                TextColumn::make('status')->badge()
                    ->colors([
                        'gray' => 'submitted',
                        'primary' => 'under_review',
                        'info' => 'approved',
                        'danger' => 'rejected',
                        'success' => 'paid',
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
                    Action::make('download_claim')
                        ->label('Download Claim Form')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function ($record) {
                            return InsuranceClaimPDFService::download($record);
                        }),

                    // View Claim Form PDF 
                    Action::make('view_claim')
                        ->label('View Claim Form')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn ($record) => route('insurance-claims.pdf', $record))
                        ->openUrlInNewTab(),

                
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