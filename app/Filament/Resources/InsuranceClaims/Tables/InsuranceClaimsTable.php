<?php

namespace App\Filament\Resources\InsuranceClaims\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class InsuranceClaimsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('claim_number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->description(fn ($record) => $record->patient->patient_number),

                TextColumn::make('insuranceProvider.company_name')
                    ->label('Insurance Provider')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('policy_number')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('claimed_amount')
                    ->label('Claimed')
                    ->money('KES')
                    ->sortable()
                    ->summarize([
                        Sum::make()
                            ->money('KES')
                            ->label('Total Claimed'),
                    ]),

                TextColumn::make('approved_amount')
                    ->label('Approved')
                    ->money('KES')
                    ->sortable()
                    ->toggleable()
                    ->summarize([
                        Sum::make()
                            ->money('KES')
                            ->label('Total Approved'),
                    ]),

                BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'submitted',
                        'warning' => 'under_review',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'info' => 'paid',
                    ])
                    ->sortable(),

                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
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
                    ])
                    ->multiple(),

                SelectFilter::make('insurance_provider_id')
                    ->label('Insurance Provider')
                    ->relationship('insuranceProvider', 'company_name')
                    ->searchable()
                    ->preload(),

                Filter::make('submitted_at')
                    ->form([
                        DatePicker::make('submitted_from')
                            ->label('Submitted From'),
                        DatePicker::make('submitted_until')
                            ->label('Submitted Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['submitted_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('submitted_at', '>=', $date),
                            )
                            ->when(
                                $data['submitted_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('submitted_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['submitted', 'under_review']))
                    ->requiresConfirmation()
                    ->form([
                        TextInput::make('approved_amount')
                            ->label('Approved Amount (KES)')
                            ->numeric()
                            ->prefix('KES')
                            ->required()
                            ->default(fn ($record) => $record->claimed_amount),
                        Textarea::make('notes')
                            ->label('Approval Notes')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'approved',
                            'approved_amount' => $data['approved_amount'],
                            'reviewed_at' => now(),
                            'reviewed_by' => Auth::id(),
                            'notes' => $data['notes'] ?? $record->notes,
                        ]);
                    })
                    ->successNotificationTitle('Claim approved successfully'),

                Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => in_array($record->status, ['submitted', 'under_review']))
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Reason for Rejection')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'reviewed_at' => now(),
                            'reviewed_by' => Auth::id(),
                        ]);
                    })
                    ->successNotificationTitle('Claim rejected'),

                Action::make('download_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->url(fn ($record) => route('insurance-claims.download-pdf', $record))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make(),

                        BulkAction::make('mark_as_under_review')
                            ->label('Mark as Under Review')
                            ->icon('heroicon-o-eye')
                            ->color('warning')
                            ->requiresConfirmation()
                            ->action(function ($records) {
                                $records->each->update(['status' => 'under_review']);
                            }),
                 ]),
            ])->defaultSort('submitted_at', 'desc');
    }
}
