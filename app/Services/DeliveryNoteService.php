<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\DeliveryNoteMail;

class DeliveryNoteService
{
    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Generate delivery note PDF and save as document
     */
    public function generateDeliveryNote(Delivery $delivery, User $user): Document
    {
        return DB::transaction(function () use ($delivery, $user) {
            // Generate PDF
            $pdf = $this->createDeliveryNotePdf($delivery);
            
            // Save PDF to temporary file
            $fileName = $this->getDeliveryNoteFileName($delivery);
            $tempPath = storage_path('app/temp/' . $fileName);
            
            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            $pdf->save($tempPath);
            
            // Create UploadedFile instance from the generated PDF
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $tempPath,
                $fileName,
                'application/pdf',
                null,
                true
            );
            
            // Upload document using DocumentService
            $document = $this->documentService->uploadDocument(
                $uploadedFile,
                $user,
                [
                    'category_id' => $this->getDeliveryNoteCategoryId(),
                    'document_type' => 'delivery_note',
                    'title' => "Delivery Note - {$delivery->delivery_number}",
                    'description' => "Delivery note for order {$delivery->order->order_number}",
                    'order_id' => $delivery->order_id,
                    'patient_id' => $delivery->order->patient_id ?? null,
                    'metadata' => [
                        'delivery_id' => $delivery->id,
                        'delivery_number' => $delivery->delivery_number,
                        'delivery_date' => $delivery->delivered_at?->toDateTimeString(),
                        'rider_name' => $delivery->rider?->full_name,
                    ],
                    'tags' => ['delivery_note', 'automated'],
                ]
            );
            
            // Update delivery with document reference
            $delivery->update([
                'delivery_note_document_id' => $document->id,
            ]);
            
            // Clean up temp file
            @unlink($tempPath);
            
            return $document;
        });
    }

    /**
     * Create the PDF for delivery note
     */
    protected function createDeliveryNotePdf(Delivery $delivery): \Barryvdh\DomPDF\PDF
    {
        $data = $this->prepareDeliveryNoteData($delivery);
        
        return Pdf::loadView('pdf.delivery-note', $data)
            ->setPaper('a4')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);
    }

    /**
     * Prepare data for delivery note
     */
    protected function prepareDeliveryNoteData(Delivery $delivery): array
    {
        $order = $delivery->order;
        
        return [
            'delivery' => $delivery,
            'order' => $order,
            'patient' => $order->patient,
            'rider' => $delivery->rider,
            'items' => $order->items,
            'generated_at' => now(),
            'company_info' => [
                'name' => config('app.name'),
                'address' => config('company.address'),
                'phone' => config('company.phone'),
                'email' => config('company.email'),
            ],
        ];
    }

    /**
     * Send delivery note via email
     */
    public function sendDeliveryNoteEmail(Delivery $delivery, ?Document $document = null): bool
    {
        try {
            // Generate document if not provided
            if (!$document) {
                $document = $delivery->deliveryNoteDocument ?? $this->generateDeliveryNote(
                    $delivery,
                    auth()->user()
                );
            }
            
            $patient = $delivery->order->prescription->patient;
            
            if (!$patient || !$patient->email) {
                throw new \Exception('Patient email not found');
            }
            
            // Get PDF path
            $pdfPath = Storage::path($document->file_path);
            
            // Send email
            Mail::to($patient->email)
                ->send(new DeliveryNoteMail($delivery, $pdfPath));
            
            // Log the action
            $document->logAccess(
                auth()->user(),
                'email_sent',
                ['recipient' => $patient->email]
            );
            
            return true;
        } catch (\Exception $e) {
            logger()->error('Failed to send delivery note email', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Generate and send delivery note
     */
    public function generateAndSendDeliveryNote(Delivery $delivery, User $user): array
    {
        try {
            $document = $this->generateDeliveryNote($delivery, $user);
            $emailSent = $this->sendDeliveryNoteEmail($delivery, $document);
            
            return [
                'success' => true,
                'document' => $document,
                'email_sent' => $emailSent,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Bulk generate delivery notes for multiple deliveries
     */
    public function bulkGenerateDeliveryNotes(array $deliveryIds, User $user): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];
        
        foreach ($deliveryIds as $deliveryId) {
            try {
                $delivery = Delivery::findOrFail($deliveryId);
                
                if ($delivery->status !== 'delivered') {
                    throw new \Exception('Delivery not in delivered status');
                }
                
                $document = $this->generateDeliveryNote($delivery, $user);
                
                $results['success'][] = [
                    'delivery_id' => $deliveryId,
                    'document_id' => $document->id,
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'delivery_id' => $deliveryId,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }

    /**
     * Bulk generate and send delivery notes
     */
    public function bulkGenerateAndSendDeliveryNotes(array $deliveryIds, User $user): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];
        
        foreach ($deliveryIds as $deliveryId) {
            try {
                $delivery = Delivery::findOrFail($deliveryId);
                
                if ($delivery->status !== 'delivered') {
                    throw new \Exception('Delivery not in delivered status');
                }
                
                $result = $this->generateAndSendDeliveryNote($delivery, $user);
                
                if ($result['success']) {
                    $results['success'][] = [
                        'delivery_id' => $deliveryId,
                        'document_id' => $result['document']->id,
                        'email_sent' => $result['email_sent'],
                    ];
                } else {
                    throw new \Exception($result['error']);
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'delivery_id' => $deliveryId,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }

    /**
     * Get delivery note category ID
     */
    protected function getDeliveryNoteCategoryId(): ?int
    {
        // Get or create delivery note category
        $category = \App\Models\DocumentCategory::firstOrCreate(
            ['name' => 'Delivery Notes'],
            [
                'description' => 'Delivery confirmation documents',
                'is_active' => true,
            ]
        );
        
        return $category->id;
    }

    /**
     * Generate file name for delivery note
     */
    protected function getDeliveryNoteFileName(Delivery $delivery): string
    {
        return sprintf(
            'delivery_note_%s_%s.pdf',
            $delivery->delivery_number,
            now()->format('YmdHis')
        );
    }
}