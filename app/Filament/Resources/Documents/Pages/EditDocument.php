<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),

            Action::make('create_version')
                ->label('Create New Version')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\FileUpload::make('new_file')
                        ->label('Upload New Version')
                        ->disk('local')
                        ->directory('documents')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                        ->maxSize(15360)
                        ->required(),
                    \Filament\Forms\Components\Textarea::make('change_notes')
                        ->label('What changed?')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $newFile = $data['new_file'];
                    if ($newFile) {
                        $filePath = $newFile->store('documents', 'local');
                        $fileSize = \Illuminate\Support\Facades\Storage::size($filePath);

                        $this->getRecord()->createVersion(
                            $filePath,
                            $fileSize,
                            auth()->user(),
                            $data['change_notes']
                        );

                        Notification::make()
                            ->title('New version created')
                            ->success()
                            ->send();
                    }
                }),

            DeleteAction::make()
                ->visible(fn () => ! $this->getRecord()->is_locked),

            ForceDeleteAction::make(),

            RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Document updated')
            ->body('The document has been updated successfully.')
            ->duration(5000);
    }

    protected function beforeSave(): void
    {
        if ($this->getRecord()->is_locked) {
            Notification::make()
                ->danger()
                ->title('Cannot edit locked document')
                ->body('This document is locked and cannot be edited. Please unlock it first.')
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        // Log the update
        $this->getRecord()->logAccess(auth()->user(), 'updated');
    }
}
