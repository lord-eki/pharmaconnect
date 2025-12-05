<?php

namespace App\Filament\Physician\Resources\Physician\ClaimForms\Pages;

use App\Filament\Physician\Resources\Physician\ClaimForms\ClaimFormResource;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Services\DocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateClaimForm extends CreateRecord
{
    protected static string $resource = ClaimFormResource::class;


     protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set physician_id if not already set
        if (empty($data['physician_id'])) {
            $data['physician_id'] = auth()->id();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $claimForm = $this->record;
        
        // If manual submission with uploaded document, create Document record
        if ($claimForm->submission_type === 'manual' && $this->data['document_path'] ?? null) {
            $this->createDocumentFromUpload($claimForm);
        }

        // If online submission, generate PDF from form data
        if ($claimForm->submission_type === 'online') {
            $this->generateClaimFormPDF($claimForm);
        }
    }

    protected function createDocumentFromUpload($claimForm): void
    {
        $filePath = $this->data['document_path'];
        
        if (!$filePath || !Storage::exists($filePath)) {
            return;
        }

        try {
            $documentService = app(DocumentService::class);
            
            // Get the uploaded file info
            $fileName = basename($filePath);
            $mimeType = Storage::mimeType($filePath);
            $fileSize = Storage::size($filePath);
            
            // Create document record
            $document = Document::create([
                'category_id' => $this->getClaimFormCategoryId(),
                'document_type' => 'claim_form',
                'title' => "Claim Form - {$claimForm->form_number}",
                'description' => "Manual claim form for prescription {$claimForm->prescription->prescription_number}",
                'file_name' => $fileName,
                'file_path' => $filePath,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'file_hash' => hash_file('sha256', Storage::path($filePath)),
                'prescription_id' => $claimForm->prescription_id,
                'insurance_provider_id' => $claimForm->insurance_provider_id,
                'patient_id' => $claimForm->patient_id,
                'uploaded_by' => auth()->id(),
                'uploaded_at' => now(),
            ]);

            // Link document to claim form
            $claimForm->update(['document_id' => $document->id]);
            
            // Log the upload
            $document->logAccess(auth()->user(), 'upload', [
                'source' => 'claim_form_creation',
                'claim_form_id' => $claimForm->id,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to create document from claim form upload', [
                'claim_form_id' => $claimForm->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function generateClaimFormPDF($claimForm): void
    {
        try {
            // Generate PDF from form data
            
            $pdf = Pdf::loadView('claim-forms.pdf', [
                'claimForm' => $claimForm,
                'prescription' => $claimForm->prescription,
                'patient' => $claimForm->patient,
                'physician' => $claimForm->physician,
                'insuranceProvider' => $claimForm->insuranceProvider,
            ]);

            $fileName = "claim_form_{$claimForm->form_number}.pdf";
            $filePath = "claim-forms/{$fileName}";
            
            // Save PDF to storage
            Storage::put($filePath, $pdf->output());

            // Create document record
            $document = Document::create([
                'category_id' => $this->getClaimFormCategoryId(),
                'document_type' => 'claim_form',
                'title' => "Claim Form - {$claimForm->form_number}",
                'description' => "Online claim form for prescription {$claimForm->prescription->prescription_number}",
                'file_name' => $fileName,
                'file_path' => $filePath,
                'mime_type' => 'application/pdf',
                'file_size' => Storage::size($filePath),
                'file_hash' => hash_file('sha256', Storage::path($filePath)),
                'prescription_id' => $claimForm->prescription_id,
                'insurance_provider_id' => $claimForm->insurance_provider_id,
                'patient_id' => $claimForm->patient_id,
                'uploaded_by' => auth()->id(),
                'uploaded_at' => now(),
            ]);

            // Link document to claim form
            $claimForm->update(['document_id' => $document->id]);

        } catch (\Exception $e) {
            \Log::error('Failed to generate claim form PDF', [
                'claim_form_id' => $claimForm->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function getClaimFormCategoryId(): int
    {
        // Get or create "Insurance Claims" category
        $category = DocumentCategory::firstOrCreate(
            ['name' => 'Insurance Claims'],
            [
                'description' => 'Documents related to insurance claims',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        return $category->id;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
