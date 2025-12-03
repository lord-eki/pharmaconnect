<?php

namespace App\Filament\Physician\Resources\Documents\Pages;

use App\Filament\Physician\Resources\Documents\DocumentResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

   protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn ($record) => !$record->is_locked),

            Action::make('download')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function ($record) {
                    $record->logAccess(auth()->user(), 'download');
                    return Storage::download($record->file_path, $record->file_name);
                }),

            Action::make('verify')
                ->label('Verify')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->form([
                    TextEntry::make('notes')
                        ->label('Verification Notes'),
                ])
                ->action(function ($record, array $data) {
                    $record->verify(auth()->user(), $data['notes'] ?? null);
                })
                ->visible(fn ($record) => $record->verification_status === 'pending'),

            Action::make('share')
                ->label('Share')
                ->icon('heroicon-o-share')
                ->color('primary')
                ->form([
                    Select::make('user_id')
                        ->label('Share With User')
                        ->relationship('users', 'name')
                        ->searchable()
                        ->required(),
                    Select::make('permission')
                        ->label('Permission')
                        ->options([
                            'view' => 'View Only',
                            'download' => 'View & Download',
                            'edit' => 'Full Access',
                        ])
                        ->default('view')
                        ->required(),
                    DateTimePicker::make('expires_at')
                        ->label('Expires At')
                        ->minDate(now()),
                ])
                ->action(function ($record, array $data) {
                    $sharedWith = \App\Models\User::find($data['user_id']);
                    $record->shareWith($sharedWith, auth()->user(), $data['permission'], $data['expires_at'] ?? null);
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document Details')
                    ->schema([
                        TextEntry::make('document_number')
                            ->label('Document Number')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('title')
                            ->label('Title')
                            ->size('lg')
                            ->weight('bold'),
                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                        TextEntry::make('document_type')
                            ->label('Type')
                            ->badge(),
                        TextEntry::make('category.name')
                            ->label('Category'),
                        TextEntry::make('verification_status')
                            ->label('Verification Status')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'pending' => 'warning',
                                'verified' => 'success',
                                'rejected' => 'danger',
                            }),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                    ])
                    ->columns(2),

                Section::make('File Information')
                    ->schema([
                        TextEntry::make('file_name')
                            ->label('File Name'),
                        TextEntry::make('mime_type')
                            ->label('File Type'),
                        TextEntry::make('file_size')
                            ->label('File Size')
                            ->formatStateUsing(fn ($record) => $record->getFileSizeFormatted()),
                        ImageEntry::make('file_path')
                            ->label('Preview')
                            ->visible(fn ($record) => $record->isImage())
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Related Entities')
                    ->schema([
                        TextEntry::make('prescription.prescription_number')
                            ->label('Prescription')
                            ->visible(fn ($record) => $record->prescription_id),
                        TextEntry::make('order.order_number')
                            ->label('Order')
                            ->visible(fn ($record) => $record->order_id),
                        TextEntry::make('insuranceClaim.claim_number')
                            ->label('Insurance Claim')
                            ->visible(fn ($record) => $record->insurance_claim_id),
                        TextEntry::make('supplier.company_name')
                            ->label('Supplier')
                            ->visible(fn ($record) => $record->supplier_id),
                        TextEntry::make('insuranceProvider.company_name')
                            ->label('Insurance Provider')
                            ->visible(fn ($record) => $record->insurance_provider_id),
                        TextEntry::make('patient.patient_number')
                            ->label('Patient')
                            ->visible(fn ($record) => $record->patient_id),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Upload & Verification')
                    ->schema([
                        TextEntry::make('uploadedBy.name')
                            ->label('Uploaded By'),
                        TextEntry::make('uploaded_at')
                            ->label('Uploaded At')
                            ->dateTime(),
                        TextEntry::make('verifiedBy.name')
                            ->label('Verified By')
                            ->visible(fn ($record) => $record->verified_by),
                        TextEntry::make('verified_at')
                            ->label('Verified At')
                            ->dateTime()
                            ->visible(fn ($record) => $record->verified_at),
                        TextEntry::make('verification_notes')
                            ->label('Verification Notes')
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record->verification_notes),
                    ])
                    ->columns(2),

                Section::make('Metadata')
                    ->schema([
                        TextEntry::make('tags')
                            ->label('Tags')
                            ->badge()
                            ->visible(fn ($record) => $record->tags),
                        TextEntry::make('version')
                            ->label('Version'),
                        IconEntry::make('is_locked')
                            ->label('Locked')
                            ->boolean(),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
        }
}
