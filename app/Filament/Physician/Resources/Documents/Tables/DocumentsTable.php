<?php

namespace App\Filament\Physician\Resources\Documents\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

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
                    ->weight('bold'),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('file_name')
                    ->label('File')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(),

                TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn ($record) => $record->getFileSizeFormatted())
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('uploadedBy.name')
                    ->label('Uploaded By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('verification_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'verified',
                        'danger' => 'rejected',
                    ])
                    ->sortable(),

                IconColumn::make('is_locked')
                    ->label('Locked')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('uploaded_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
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
                    ]),

                SelectFilter::make('verification_status')
                    ->label('Verification Status')
                    ->options([
                        'pending' => 'Pending',
                        'verified' => 'Verified',
                        'rejected' => 'Rejected',
                    ]),

                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'archived' => 'Archived',
                        'deleted' => 'Deleted',
                    ]),

                TernaryFilter::make('is_locked')
                    ->label('Locked')
                    ->placeholder('All documents')
                    ->trueLabel('Locked only')
                    ->falseLabel('Unlocked only'),

                Filter::make('uploaded_at')
                    ->form([
                        DatePicker::make('uploaded_from')
                            ->label('Uploaded From'),
                        DatePicker::make('uploaded_until')
                            ->label('Uploaded Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['uploaded_from'], fn ($q, $date) => $q->whereDate('uploaded_at', '>=', $date))
                            ->when($data['uploaded_until'], fn ($q, $date) => $q->whereDate('uploaded_at', '<=', $date));
                    }),            ])
            ->recordActions([
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
