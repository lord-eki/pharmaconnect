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
            
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $tempPath,
                $fileName,
                'application/pdf',
                null,
                true
            );
            
            $patient = $this->getPatientFromDelivery($delivery);
            
            $document = $this->documentService->uploadDocument(
                $uploadedFile,
                $user,
                [
                    'category_id' => $this->getDeliveryNoteCategoryId(),
                    'document_type' => 'delivery_note',
                    'title' => "Delivery Note - {$delivery->delivery_number}",
                    'description' => "Delivery note for delivery {$delivery->delivery_number}",
                    'patient_id' => $patient?->id,
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
     * Get patient from delivery (handles different relationship structures)
     */
    protected function getPatientFromDelivery(Delivery $delivery)
    {
        // Try different relationship paths
        if ($delivery->prescription && $delivery->prescription->patient) {
            return $delivery->prescription->patient;
        }
        
        if ($delivery->order && $delivery->order->patient) {
            return $delivery->order->patient;
        }
        
        if ($delivery->order && $delivery->order->prescription && $delivery->order->prescription->patient) {
            return $delivery->order->prescription->patient;
        }
        
        // If delivery has multiple orders, get patient from first order
        $firstOrder = $delivery->orders()->with('prescription.patient')->first();
        if ($firstOrder) {
            if ($firstOrder->patient) {
                return $firstOrder->patient;
            }
            if ($firstOrder->prescription && $firstOrder->prescription->patient) {
                return $firstOrder->prescription->patient;
            }
        }
        
        return null;
    }

    /**
     * Create the PDF for delivery note
     */
    protected function createDeliveryNotePdf(Delivery $delivery): \Barryvdh\DomPDF\PDF
    {
        $data = $this->prepareDeliveryNoteData($delivery);
        
        // Use pdf.delivery-note-pdf for PDF generation, or pdf.delivery-note if that's your preferred view
        return Pdf::loadView('pdf.delivery-note-pdf', $data)
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
        $patient = $this->getPatientFromDelivery($delivery);
        
        // Get orders - handle both single order and multiple orders
        $orders = $delivery->orders ?? collect([$delivery->order])->filter();
        
        return [
            'delivery' => $delivery,
            'orders' => $orders,
            'patient' => $patient,
            'rider' => $delivery->rider,
            'prescription' => $delivery->prescription,
            'items' => $orders->flatMap->items ?? collect(),
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
            
            $patient = $this->getPatientFromDelivery($delivery);
            
            if (!$patient || !$patient->email) {
                throw new \Exception('Patient email not found');
            }
            
            // Get PDF path
            $pdfPath = Storage::path($document->file_path);
            
            if (!file_exists($pdfPath)) {
                throw new \Exception('Delivery note PDF file not found');
            }
            
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
                'trace' => $e->getTraceAsString(),
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
            logger()->error('Failed to generate and send delivery note', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
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