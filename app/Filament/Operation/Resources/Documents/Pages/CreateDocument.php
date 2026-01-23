<?php

namespace App\Filament\Operation\Resources\Documents\Pages;

use App\Filament\Operation\Resources\Documents\DocumentResource;
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
            ->title('Document uploaded')
            ->body('The document has been uploaded successfully.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by'] = auth()->id();
        $data['uploaded_at'] = now();
        $data['verification_status'] = $data['verification_status'] ?? 'pending';
        $data['status'] = $data['status'] ?? 'active';
        $data['version'] = $data['version'] ?? 1;

        return $data;
    }
}

