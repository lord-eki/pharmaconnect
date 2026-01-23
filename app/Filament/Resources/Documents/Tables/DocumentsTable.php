<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Models\Document;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
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
                    ->label('Document #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Document number copied')
                    ->weight('bold'),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn (Document $record): string => $record->title)
                    ->description(fn (Document $record): ?string => $record->description ? \Illuminate\Support\Str::limit($record->description, 60) : null),

                BadgeColumn::make('document_type')
                    ->label('Type')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucwords($state, '_')))
                    ->colors([
                        'primary' => 'claim_form',
                        'success' => ['invoice', 'receipt'],
                        'warning' => ['delivery_note', 'credit_note'],
                        'info' => ['contract', 'agreement'],
                        'danger' => 'other',
                    ]),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                BadgeColumn::make('verification_status')
                    ->label('Verification')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'verified',
                        'danger' => 'rejected',
                    ])
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'verified' => 'heroicon-o-check-circle',
                        'rejected' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    }),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->colors([
                        'success' => 'active',
                        'warning' => 'archived',
                        'danger' => 'deleted',
                    ]),

                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->toggleable()
                    ->description(fn (Document $record): ?string => $record->patient?->patient_number),

                TextColumn::make('insuranceProvider.company_name')
                    ->label('Insurance Provider')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->limit(30),

                TextColumn::make('supplier.company_name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->limit(30),

                TextColumn::make('insuranceClaim.claim_number')
                    ->label('Claim #')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('prescription.prescription_number')
                    ->label('Prescription #')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_locked')
                    ->label('Locked')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->toggleable(),

                TextColumn::make('version')
                    ->label('Version')
                    ->formatStateUsing(fn ($state) => "v{$state}")
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('file_size')
                    ->label('File Size')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1024, 2).' KB' : 'N/A')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('mime_type')
                    ->label('File Type')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

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

                TextColumn::make('tags')
                    ->label('Tags')
                    ->badge()
                    ->separator(',')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('shares_count')
                    ->counts('shares')
                    ->label('Shares')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('accessLogs_count')
                    ->counts('accessLogs')
                    ->label('Access Count')
                    ->badge()
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),

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
                        'contract' => 'Contract',
                        'agreement' => 'Agreement',
                        'compliance_doc' => 'Compliance Document',
                        'audit_report' => 'Audit Report',
                        'policy_doc' => 'Policy Document',
                        'report' => 'Report',
                        'other' => 'Other',
                    ])
                    ->multiple()
                    ->searchable(),

                SelectFilter::make('verification_status')
                    ->label('Verification Status')
                    ->options([
                        'pending' => 'Pending',
                        'verified' => 'Verified',
                        'rejected' => 'Rejected',
                    ])
                    ->multiple(),

                SelectFilter::make('status')
                    ->label('Document Status')
                    ->options([
                        'active' => 'Active',
                        'archived' => 'Archived',
                        'deleted' => 'Deleted',
                    ])
                    ->multiple(),

                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),

                SelectFilter::make('insurance_provider')
                    ->relationship('insuranceProvider', 'company_name')
                    ->multiple()
                    ->preload()
                    ->searchable(),

                SelectFilter::make('supplier')
                    ->relationship('supplier', 'company_name')
                    ->multiple()
                    ->preload()
                    ->searchable(),

                SelectFilter::make('uploaded_by')
                    ->relationship('uploadedBy', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),

                Filter::make('is_locked')
                    ->label('Locked Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_locked', true))
                    ->toggle(),

                Filter::make('has_versions')
                    ->label('Has Versions')
                    ->query(fn (Builder $query): Builder => $query->has('versions'))
                    ->toggle(),

                Filter::make('shared')
                    ->label('Shared Documents')
                    ->query(fn (Builder $query): Builder => $query->has('shares'))
                    ->toggle(),

                Filter::make('uploaded_today')
                    ->label('Uploaded Today')
                    ->query(callback: fn (Builder $query): Builder => $query->whereDate('uploaded_at', today())),

                Filter::make('uploaded_this_week')
                    ->label('This Week')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('uploaded_at', [now()->startOfWeek(), now()->endOfWeek()])),

                Filter::make('uploaded_this_month')
                    ->label('This Month')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('uploaded_at', now()->month)),

                Filter::make('verified_today')
                    ->label('Verified Today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('verified_at', today())),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),

                    EditAction::make(),

                    Action::make('preview')
                        ->label('Preview')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->visible(fn (Document $record) => $record->canBeViewed())
                        ->url(fn (Document $record) => $record->getFileUrl())
                        ->openUrlInNewTab(),

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
                                ->body('The document file could not be located.')
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
                        ->modalDescription('Confirm that this document has been reviewed and verified.')
                        ->modalSubmitActionLabel('Verify Document')
                        ->form([
                            Textarea::make('verification_notes')
                                ->label('Verification Notes (Optional)')
                                ->rows(3),
                        ])
                        ->action(function (Document $record, array $data) {
                            $record->verify(auth()->user(), $data['verification_notes'] ?? null);

                            Notification::make()
                                ->title('Document verified')
                                ->body('The document has been successfully verified.')
                                ->success()
                                ->send();
                        }),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Document $record) => $record->verification_status === 'pending')
                        ->requiresConfirmation()
                        ->modalHeading('Reject Document')
                        ->modalDescription('Please provide a reason for rejecting this document.')
                        ->form([
                            Textarea::make('rejection_notes')
                                ->label('Rejection Reason')
                                ->required()
                                ->rows(4)
                                ->placeholder('Explain why this document is being rejected...'),
                        ])
                        ->action(function (Document $record, array $data) {
                            $record->reject(auth()->user(), $data['rejection_notes']);

                            Notification::make()
                                ->title('Document rejected')
                                ->body('The document has been rejected.')
                                ->success()
                                ->send();
                        }),

                    Action::make('lock')
                        ->label('Lock')
                        ->icon('heroicon-o-lock-closed')
                        ->color('warning')
                        ->visible(fn (Document $record) => ! $record->is_locked)
                        ->requiresConfirmation()
                        ->modalHeading('Lock Document')
                        ->modalDescription('Locked documents cannot be edited or deleted. Continue?')
                        ->action(function (Document $record) {
                            $record->lock();

                            Notification::make()
                                ->title('Document locked')
                                ->success()
                                ->send();
                        }),

                    Action::make('unlock')
                        ->label('Unlock')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->visible(fn (Document $record) => $record->is_locked)
                        ->requiresConfirmation()
                        ->modalHeading('Unlock Document')
                        ->modalDescription('This will allow the document to be edited and deleted. Continue?')
                        ->action(function (Document $record) {
                            $record->unlock();

                            Notification::make()
                                ->title('Document unlocked')
                                ->success()
                                ->send();
                        }),

                    Action::make('archive')
                        ->label('Archive')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->visible(fn (Document $record) => $record->status === 'active')
                        ->requiresConfirmation()
                        ->action(function (Document $record) {
                            $record->archive();

                            Notification::make()
                                ->title('Document archived')
                                ->success()
                                ->send();
                        }),

                    Action::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn (Document $record) => $record->status === 'archived')
                        ->requiresConfirmation()
                        ->action(function (Document $record) {
                            $record->status = 'active';
                            $record->save();

                            Notification::make()
                                ->title('Document activated')
                                ->success()
                                ->send();
                        }),

                    Action::make('view_access_logs')
                        ->label('Access Logs')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->color('info')
                        ->url(fn (Document $record) => route('filament.Admin.resources.documents.view', ['record' => $record]).'?activeTab=access-management'),

                    DeleteAction::make()
                        ->visible(fn (Document $record) => ! $record->is_locked),

                    ForceDeleteAction::make(),

                    RestoreBulkAction::make(),
                ]),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function ($records) {
                            $lockedCount = $records->where('is_locked', true)->count();
                            if ($lockedCount > 0) {
                                Notification::make()
                                    ->title('Cannot delete locked documents')
                                    ->body("{$lockedCount} locked document(s) cannot be deleted.")
                                    ->warning()
                                    ->send();

                                return false;
                            }
                        }),

                    ForceDeleteBulkAction::make(),

                    RestoreBulkAction::make(),

                    BulkAction::make('verify_bulk')
                        ->label('Verify Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->verification_status === 'pending') {
                                    $record->verify(auth()->user());
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title('Documents verified')
                                ->body("{$count} document(s) verified successfully.")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('archive_bulk')
                        ->label('Archive Selected')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'active') {
                                    $record->archive();
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title('Documents archived')
                                ->body("{$count} document(s) archived successfully.")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('lock_bulk')
                        ->label('Lock Selected')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (! $record->is_locked) {
                                    $record->lock();
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title('Documents locked')
                                ->body("{$count} document(s) locked successfully.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])->defaultSort('uploaded_at', 'desc')
            ->poll('60s')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistFiltersInSession()
            ->persistSortInSession();
    }
}
