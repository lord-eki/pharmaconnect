<?php

namespace App\Filament\Supplier\Resources\Supplier\Quotations\Tables;

use App\Models\Quotation;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quotation_number')
                    ->label('Quotation #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('prescription.prescription_number')
                    ->label('Prescription #')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('prescription.physician.name')
                    ->label('Physician')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('prescription.patient.first_name')
                    ->label('Patient')
                    ->formatStateUsing(fn ($record) => 
                        $record->prescription->patient 
                            ? "{$record->prescription->patient->first_name} {$record->prescription->patient->last_name}"
                            : 'N/A'
                    )
                    ->searchable(['first_name', 'last_name'])
                    ->wrap(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('KES')
                    ->sortable()
                    ->summarize([
                        Sum::make()
                            ->money('KES')
                            ->label('Total Value'),
                    ]),

                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'sent' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('valid_until')
                    ->label('Valid Until')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'success')
                    ->description(fn ($state) => $state ? $state->diffForHumans() : null),

                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending Response',
                        'sent' => 'Sent',
                        'accepted' => 'Accepted',
                        'rejected' => 'Rejected',
                        'expired' => 'Expired',
                    ])
                    ->default('pending'),

                Filter::make('valid')
                    ->label('Still Valid')
                    ->query(fn (Builder $query) => $query->where('valid_until', '>', now())),

                Filter::make('expired')
                    ->label('Expired')
                    ->query(fn (Builder $query) => $query->where('valid_until', '<=', now())),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Requested From'),
                        DatePicker::make('created_until')
                            ->label('Requested Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    
                    EditAction::make()
                        ->visible(fn ($record) => $record->status === 'pending' && !$record->valid_until->isPast()),

                    Action::make('respond')
                        ->label('Respond to Quotation')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'pending' && !$record->valid_until->isPast())
                        ->requiresConfirmation()
                        ->modalHeading('Submit Quotation Response')
                        ->modalDescription('Are you sure you want to submit your quotation response? This will send your prices to the requesting physician.')
                        ->action(function (Quotation $record) {
                            // Update quotation status
                            $record->update(['status' => 'sent']);
                            
                            // Notify physician (placeholder for notification system)
                            // Notification::send($record->prescription->physician, new QuotationResponseNotification($record));
                            
                            // return redirect()->route('filament.supplier.resources.quotations.view', ['record' => $record]);
                        })
                        ->successNotificationTitle('Quotation response submitted successfully'),
                        // ->after(fn () => redirect()->route('filament.supplier.resources.quotations.index')),

                    Action::make('decline')
                        ->label('Decline Request')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => $record->status === 'pending')
                        ->requiresConfirmation()
                        ->modalHeading('Decline Quotation Request')
                        ->modalDescription('Are you sure you want to decline this quotation request? This action cannot be undone.')
                        ->form([
                            Textarea::make('rejection_reason')
                                ->label('Reason for Declining')
                                ->required()
                                ->maxLength(500)
                                ->placeholder('Please provide a reason for declining this request'),
                        ])
                        ->action(function (Quotation $record, array $data) {
                            $record->update([
                                'status' => 'rejected',
                                'notes' => $data['rejection_reason'],
                            ]);
                            
                            // Mark all items as unavailable
                            $record->quotationItems()->update(['available' => false]);
                        })
                        ->successNotificationTitle('Quotation request declined'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
