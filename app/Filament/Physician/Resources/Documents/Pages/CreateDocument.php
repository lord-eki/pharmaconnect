<?php

namespace App\Filament\Physician\Resources\Documents\Pages;

use App\Filament\Physician\Resources\Documents\DocumentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set uploaded_by to current user
        $data['uploaded_by'] = auth()->id();
        $data['uploaded_at'] = now();

        // Handle file upload
        if (isset($data['file_path'])) {
            $file = $data['file_path'];

            // Store additional file information
            $data['file_name'] = basename($file);
            $data['mime_type'] = Storage::mimeType($file);
            $data['file_size'] = Storage::size($file);

            // Generate file hash for duplicate detection
            $filePath = Storage::path($file);
            $data['file_hash'] = hash_file('sha256', $filePath);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
