<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentService
{
    /**
     * Upload a new document
     */
    public function uploadDocument(
        UploadedFile $file,
        User $user,
        array $data
    ): Document {
        return DB::transaction(function () use ($file, $user, $data) {
            // Generate unique file name
            $fileName = $this->generateUniqueFileName($file);
            
            // Store file
            $path = $file->storeAs('documents', $fileName, 'local');
            
            // Calculate file hash
            $fileHash = hash_file('sha256', $file->getRealPath());
            
            // Check for duplicate
            $duplicate = Document::where('file_hash', $fileHash)->first();
            if ($duplicate) {
                throw new \Exception('This document has already been uploaded. Document #: ' . $duplicate->document_number);
            }
            
            // Create document record
            $document = Document::create([
                'category_id' => $data['category_id'],
                'document_type' => $data['document_type'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_hash' => $fileHash,
                'prescription_id' => $data['prescription_id'] ?? null,
                'order_id' => $data['order_id'] ?? null,
                'insurance_claim_id' => $data['insurance_claim_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'insurance_provider_id' => $data['insurance_provider_id'] ?? null,
                'patient_id' => $data['patient_id'] ?? null,
                'uploaded_by' => $user->id,
                'uploaded_at' => now(),
                'metadata' => $data['metadata'] ?? null,
                'tags' => $data['tags'] ?? null,
            ]);
            
            // Log the upload
            $document->logAccess($user, 'upload');
            
            return $document;
        });
    }

    /**
     * Update an existing document
     */
    public function updateDocument(Document $document, array $data, ?UploadedFile $newFile = null): Document
    {
        return DB::transaction(function () use ($document, $data, $newFile) {
            if ($document->is_locked) {
                throw new \Exception('Cannot update a locked document');
            }
            
            // If new file is uploaded, create a new version
            if ($newFile) {
                $fileName = $this->generateUniqueFileName($newFile);
                $path = $newFile->storeAs('documents', $fileName, 'local');
                
                // Create version history
                $document->createVersion(
                    $path,
                    $newFile->getSize(),
                    auth()->user(),
                    $data['change_notes'] ?? 'Document updated'
                );
                
                // Update file information
                $data['file_path'] = $path;
                $data['file_name'] = $newFile->getClientOriginalName();
                $data['mime_type'] = $newFile->getMimeType();
                $data['file_size'] = $newFile->getSize();
                $data['file_hash'] = hash_file('sha256', $newFile->getRealPath());
                $data['version'] = $document->version + 1;
            }
            
            $document->update($data);
            
            return $document->fresh();
        });
    }

    /**
     * Delete a document
     */
    public function deleteDocument(Document $document, bool $permanent = false): bool
    {
        if ($document->is_locked) {
            throw new \Exception('Cannot delete a locked document');
        }
        
        return DB::transaction(function () use ($document, $permanent) {
            if ($permanent) {
                // Move file to archives
                if (Storage::exists($document->file_path)) {
                    $archivePath = 'archives/' . $document->file_path;
                    Storage::move($document->file_path, $archivePath);
                }
                
                return $document->forceDelete();
            }
            
            return $document->delete();
        });
    }

    /**
     * Bulk upload documents
     */
    public function bulkUpload(array $files, User $user, array $commonData): array
    {
        $uploaded = [];
        $errors = [];
        
        foreach ($files as $file) {
            try {
                $data = array_merge($commonData, [
                    'title' => $file->getClientOriginalName(),
                ]);
                
                $document = $this->uploadDocument($file, $user, $data);
                $uploaded[] = $document;
            } catch (\Exception $e) {
                $errors[] = [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return [
            'uploaded' => $uploaded,
            'errors' => $errors,
        ];
    }

    /**
     * Link document to entities
     */
    public function linkToEntities(Document $document, array $entities): Document
    {
        $document->update([
            'prescription_id' => $entities['prescription_id'] ?? $document->prescription_id,
            'order_id' => $entities['order_id'] ?? $document->order_id,
            'insurance_claim_id' => $entities['insurance_claim_id'] ?? $document->insurance_claim_id,
            'supplier_id' => $entities['supplier_id'] ?? $document->supplier_id,
            'insurance_provider_id' => $entities['insurance_provider_id'] ?? $document->insurance_provider_id,
            'patient_id' => $entities['patient_id'] ?? $document->patient_id,
        ]);
        
        return $document->fresh();
    }

    /**
     * Get documents for a specific entity
     */
    public function getDocumentsForEntity(string $entityType, int $entityId): \Illuminate\Database\Eloquent\Collection
    {
        $column = match ($entityType) {
            'prescription' => 'prescription_id',
            'order' => 'order_id',
            'claim' => 'insurance_claim_id',
            'supplier' => 'supplier_id',
            'insurance' => 'insurance_provider_id',
            'patient' => 'patient_id',
            default => throw new \InvalidArgumentException('Invalid entity type'),
        };
        
        return Document::where($column, $entityId)
            ->orderBy('uploaded_at', 'desc')
            ->get();
    }

    /**
     * Search documents
     */
    public function searchDocuments(string $query, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $builder = Document::query();
        
        // Text search
        $builder->where(function ($q) use ($query) {
            $q->where('title', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->orWhere('document_number', 'like', "%{$query}%")
                ->orWhere('file_name', 'like', "%{$query}%");
        });
        
        // Apply filters
        if (isset($filters['document_type'])) {
            $builder->where('document_type', $filters['document_type']);
        }
        
        if (isset($filters['verification_status'])) {
            $builder->where('verification_status', $filters['verification_status']);
        }
        
        if (isset($filters['category_id'])) {
            $builder->where('category_id', $filters['category_id']);
        }
        
        if (isset($filters['uploaded_from'])) {
            $builder->whereDate('uploaded_at', '>=', $filters['uploaded_from']);
        }
        
        if (isset($filters['uploaded_to'])) {
            $builder->whereDate('uploaded_at', '<=', $filters['uploaded_to']);
        }
        
        return $builder->orderBy('uploaded_at', 'desc')->get();
    }

    /**
     * Get document statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_documents' => Document::count(),
            'pending_verification' => Document::where('verification_status', 'pending')->count(),
            'verified_documents' => Document::where('verification_status', 'verified')->count(),
            'rejected_documents' => Document::where('verification_status', 'rejected')->count(),
            'total_storage' => Document::sum('file_size'),
            'documents_by_type' => Document::select('document_type', DB::raw('COUNT(*) as count'))
                ->groupBy('document_type')
                ->pluck('count', 'document_type')
                ->toArray(),
            'documents_this_month' => Document::whereMonth('uploaded_at', now()->month)
                ->whereYear('uploaded_at', now()->year)
                ->count(),
            'documents_last_month' => Document::whereMonth('uploaded_at', now()->subMonth()->month)
                ->whereYear('uploaded_at', now()->subMonth()->year)
                ->count(),
        ];
    }

    /**
     * Generate unique file name
     */
    protected function generateUniqueFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $basename = Str::slug($basename);
        
        return $basename . '_' . time() . '_' . Str::random(8) . '.' . $extension;
    }

    /**
     * Verify multiple documents at once
     */
    public function bulkVerify(array $documentIds, User $verifier, ?string $notes = null): int
    {
        $count = 0;
        
        foreach ($documentIds as $id) {
            $document = Document::find($id);
            if ($document && $document->verification_status === 'pending') {
                $document->verify($verifier, $notes);
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Archive old documents
     */
    public function archiveOldDocuments(int $daysOld = 365): int
    {
        $documents = Document::where('status', 'active')
            ->where('uploaded_at', '<=', now()->subDays($daysOld))
            ->get();
        
        $count = 0;
        foreach ($documents as $document) {
            if ($document->archive()) {
                $count++;
            }
        }
        
        return $count;
    }
}