<?php

namespace App\Filament\Resources\InsuranceClaims\Tables;

use App\Services\InsuranceClaimPDFService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\Summarizers\Sum;
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

                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    // Download Claim Form PDF
                    Action::make('download_claim')
                        ->label('Download Claim Form')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function ($record) {
                            return InsuranceClaimPDFService::download($record);
                        }),

                    // View Claim Form PDF (in browser)
                    Action::make('view_claim')
                        ->label('View Claim Form')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn ($record) => route('insurance-claims.pdf', $record))
                        ->openUrlInNewTab(),

                    // Email Claim Form
                    Action::make('email_claim')
                        ->label('Email Claim Form')
                        ->icon('heroicon-o-envelope')
                        ->color('warning')
                        ->form([
                            TextInput::make('email')
                                ->label('Email Address')
                                ->email()
                                ->required()
                                ->default(fn ($record) => $record->insuranceProvider->email),
                            Textarea::make('message')
                                ->label('Message')
                                ->rows(3)
                                ->default('Please find attached the insurance claim form for review.'),
                        ])
                        ->action(function ($record, array $data) {
                            // \Mail::to($data['email'])->send(
                            //     new \App\Mail\InsuranceClaimFormMail($record, $data['message'])
                            // );

                            Notification::make()
                                ->title('Claim form sent')
                                ->success()
                                ->send();
                        }),

                ])->button()
                    ->label('Actions'),

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
