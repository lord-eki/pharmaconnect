<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Document created successfully')
            ->body('The document has been uploaded and saved to the system.')
            ->duration(5000);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by'] = auth()->id();
        $data['uploaded_at'] = now();
        $data['verification_status'] = $data['verification_status'] ?? 'pending';
        $data['status'] = $data['status'] ?? 'active';
        $data['version'] = $data['version'] ?? 1;
        $data['is_locked'] = $data['is_locked'] ?? false;

        return $data;
    }

    protected function afterCreate(): void
    {
        // Log the creation
        $this->getRecord()->logAccess(auth()->user(), 'created');
    }
}
