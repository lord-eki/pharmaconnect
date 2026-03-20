<?php

namespace App\Filament\Operation\Resources\Documents\Pages;

use App\Filament\Operation\Resources\Documents\DocumentResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

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
        $path = $data['file_path'] ?? null;

        if (! empty($path)) {
            $fullPath = Storage::disk('local')->path($path);

            $data['file_name'] = empty($data['file_name']) ? basename($path) : $data['file_name'];
            $data['mime_type'] = empty($data['mime_type']) ? (mime_content_type($fullPath) ?: 'application/octet-stream') : $data['mime_type'];
            $data['file_size'] = empty($data['file_size']) ? Storage::disk('local')->size($path) : $data['file_size'];
            $data['file_hash'] = empty($data['file_hash']) ? hash_file('sha256', $fullPath) : $data['file_hash'];
        }

        $data['uploaded_by'] = auth()->id();
        $data['uploaded_at'] = now();
        $data['verification_status'] = $data['verification_status'] ?? 'pending';
        $data['status'] = $data['status'] ?? 'active';
        $data['version'] = $data['version'] ?? 1;

        return $data;
    }
}
