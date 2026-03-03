<?php

namespace App\Filament\Operation\Resources\Documents\Tables;

use App\Models\Document;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')
                    ->label('Doc Number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Document number copied'),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn (Document $record): string => $record->title),

                BadgeColumn::make('document_type')
                    ->label('Type')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucwords($state, '_')))
                    ->colors([
                        'primary' => 'claim_form',
                        'success' => 'invoice',
                        'warning' => 'receipt',
                        'danger' => 'other',
                    ]),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                BadgeColumn::make('verification_status')
                    ->label('Status')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->colors([
                        'gray' => 'pending',
                        'success' => 'verified',
                        'danger' => 'rejected',
                    ]),

                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('insuranceProvider.company_name')
                    ->label('Insurer')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('supplier.company_name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_locked')
                    ->label('Locked')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1024, 2).' KB' : 'N/A')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('uploadedBy.name')
                    ->label('Uploaded By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('uploaded_at')
                    ->label('Upload Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('verifiedBy.name')
                    ->label('Verified By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('verified_at')
                    ->label('Verified Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->label('Document Type')
                    ->options([
                        'claim_form' => 'Claim Form',
                        'prescription' => 'Prescription',
                        'invoice' => 'Invoice',
                        'receipt' => 'Receipt',
                        'delivery_note' => 'Delivery Note',
                        'credit_note' => 'Credit Note',
                        'purchase_order' => 'Purchase Order',
                        'payment_voucher' => 'Payment Voucher',
                        'other' => 'Other',
                    ])
                    ->multiple(),

                SelectFilter::make('verification_status')
                    ->label('Verification Status')
                    ->options([
                        'pending' => 'Pending',
                        'verified' => 'Verified',
                        'rejected' => 'Rejected',
                    ])
                    ->multiple(),

                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload(),

                SelectFilter::make('insurance_provider')
                    ->relationship('insuranceProvider', 'company_name')
                    ->multiple()
                    ->preload(),

                SelectFilter::make('supplier')
                    ->relationship('supplier', 'company_name')
                    ->multiple()
                    ->preload(),

                Filter::make('is_locked')
                    ->label('Locked Documents')
                    ->query(fn (Builder $query): Builder => $query->where('is_locked', true)),

                Filter::make('uploaded_today')
                    ->label('Uploaded Today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('uploaded_at', today())),

                Filter::make('uploaded_this_week')
                    ->label('Uploaded This Week')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('uploaded_at', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),

                    EditAction::make(),

                    Action::make('download')
                        ->label('Download')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (Document $record) {
                            if (Storage::exists($record->file_path)) {
                                $record->logAccess(auth()->user(), 'download');

                                return Storage::download($record->file_path, $record->file_name);
                            }

                            Notification::make()
                                ->title('File not found')
                                ->danger()
                                ->send();
                        }),

                    Action::make('verify')
                        ->label('Verify')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Document $record) => $record->verification_status === 'pending')
                        ->requiresConfirmation()
                        ->modalHeading('Verify Document')
                        ->modalDescription('Are you sure you want to verify this document?')
                        ->modalSubmitActionLabel('Verify')
                        ->action(function (Document $record) {
                            $record->verify(auth()->user());

                            Notification::make()
                                ->title('Document verified successfully')
                                ->success()
                                ->send();
                        }),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Document $record) => $record->verification_status === 'pending')
                        ->requiresConfirmation()
                        ->form([
                            \Filament\Forms\Components\Textarea::make('rejection_notes')
                                ->label('Rejection Reason')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (Document $record, array $data) {
                            $record->reject(auth()->user(), $data['rejection_notes']);

                            Notification::make()
                                ->title('Document rejected')
                                ->success()
                                ->send();
                        }),

                    Action::make('lock')
                        ->label('Lock')
                        ->icon('heroicon-o-lock-closed')
                        ->color('warning')
                        ->visible(fn (Document $record) => ! $record->is_locked)
                        ->requiresConfirmation()
                        ->action(function (Document $record) {
                            $record->lock();

                            Notification::make()
                                ->title('Document locked successfully')
                                ->success()
                                ->send();
                        }),

                    Action::make('unlock')
                        ->label('Unlock')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->visible(fn (Document $record) => $record->is_locked)
                        ->requiresConfirmation()
                        ->action(function (Document $record) {
                            $record->unlock();

                            Notification::make()
                                ->title('Document unlocked successfully')
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make()
                        ->visible(fn (Document $record) => ! $record->is_locked),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function ($records) {
                            $lockedCount = $records->where('is_locked', true)->count();
                            if ($lockedCount > 0) {
                                Notification::make()
                                    ->title("Cannot delete {$lockedCount} locked document(s)")
                                    ->warning()
                                    ->send();

                                return false;
                            }
                        }),

                    BulkAction::make('verify_bulk')
                        ->label('Verify Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->verification_status === 'pending') {
                                    $record->verify(auth()->user());
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("{$count} document(s) verified successfully")
                                ->success()
                                ->send();
                        }),
                ]),
            ])->defaultSort('uploaded_at', 'desc')
            ->poll('30s');
    }
}
