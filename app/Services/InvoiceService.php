<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Invoice;
use App\Mail\InvoiceToInsurance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    /**
     * Generate invoice number
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ym');
        
        // Get the last invoice number for this month
        $lastInvoice = Invoice::where('invoice_number', 'LIKE', "{$prefix}{$date}%")
            ->orderBy('invoice_number', 'desc')
            ->first();
        
        if ($lastInvoice) {
            // Extract sequence number and increment
            $lastSequence = (int) substr($lastInvoice->invoice_number, -5);
            $sequence = str_pad($lastSequence + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $sequence = '00001';
        }
        
        return "{$prefix}{$date}-{$sequence}";
    }

    /**
     * Create invoice from delivered order
     */
    public function createInvoiceFromOrder(Order $order, array $data = []): Invoice
    {
        // Validate that order is delivered
        if ($order->status !== 'delivered') {
            throw new \Exception('Invoice can only be generated for delivered orders');
        }

        // Check if patient has insurance
        $patient = $order->prescription?->patient;
        if (!$patient || !$patient->insurance_provider_id) {
            throw new \Exception('Patient must have insurance to generate insurance invoice');
        }

        // Check if invoice already exists
        $existingInvoice = Invoice::where('order_id', $order->id)->first();
        if ($existingInvoice) {
            throw new \Exception('Invoice already exists for this order');
        }

        // Calculate amounts
        $subtotal = $order->items->sum('total_price');
        $taxAmount = $order->items->sum(function($item) {
            return $item->tax_amount ?? 0;
        });
        $discountAmount = $data['discount_amount'] ?? 0;
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        // Create invoice
        $invoice = Invoice::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'order_id' => $order->id,
            'insurance_provider_id' => $patient->insurance_provider_id,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'currency' => $data['currency'] ?? 'KES',
            'due_date' => $data['due_date'] ?? now()->addDays(30),
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);

        Log::info('Invoice created', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'order_id' => $order->id,
        ]);

        return $invoice;
    }

    /**
     * Generate invoice PDF
     */
    public function generateInvoicePDF(Invoice $invoice): string
    {
        try {
            // Load all required relationships
            $invoice->load([
                'order.prescription.patient.insuranceProvider',
                'order.prescription.physician',
                'order.items.medicine',
                'order.supplier',
            ]);

            // Generate PDF
            $pdf = Pdf::loadView('pdf.invoice', [
                'invoice' => $invoice
            ]);

            $pdf->setPaper('a4', 'portrait');
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', false);

            $fileName = "Invoice_" . preg_replace('/[^A-Za-z0-9_-]/', '_', $invoice->invoice_number) . "_" . time() . ".pdf";
            $path = "reports/invoices/{$fileName}";
            
            Storage::disk('public')->put($path, $pdf->output());
            
            Log::info('Invoice PDF generated', [
                'invoice_id' => $invoice->id,
                'path' => $path,
            ]);

            return $path;
        } catch (\Exception $e) {
            Log::error('Error generating invoice PDF', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Download invoice PDF
     */
    public function downloadInvoicePDF(Invoice $invoice): \Illuminate\Http\Response
    {
        try {
            $invoice->load([
                'order.prescription.patient.insuranceProvider',
                'order.prescription.physician',
                'order.items.medicine',
                'order.supplier',
            ]);

            $pdf = Pdf::loadView('reports.invoice', [
                'invoice' => $invoice
            ]);

            $pdf->setPaper('a4', 'portrait');
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', false);

            $fileName = "Invoice_" . preg_replace('/[^A-Za-z0-9_-]/', '_', $invoice->invoice_number) . ".pdf";
            
            return $pdf->download($fileName);
        } catch (\Exception $e) {
            Log::error('Error downloading invoice PDF', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send invoice to insurance company via email
     */
    public function sendInvoiceToInsurance(Invoice $invoice, array $options = []): bool
    {
        try {
            // Load relationships
            $invoice->load([
                'order.prescription.patient.insuranceProvider',
                'order.prescription.physician',
                'order.items.medicine',
            ]);

            // Get insurance provider
            $insuranceProvider = $invoice->order->prescription->patient->insuranceProvider;
            
            if (!$insuranceProvider) {
                throw new \Exception('Insurance provider not found');
            }

            if (!$insuranceProvider->email) {
                throw new \Exception('Insurance provider email not found');
            }

            // Generate PDF
            $pdfPath = $this->generateInvoicePDF($invoice);
            $fullPath = Storage::disk('public')->path($pdfPath);

            // Prepare email data
            $emailData = [
                'invoice' => $invoice,
                'insuranceProvider' => $insuranceProvider,
                'patient' => $invoice->order->prescription->patient,
                'additionalMessage' => $options['message'] ?? null,
            ];

            // Send email
            Mail::to($insuranceProvider->email)
                ->cc($options['cc'] ?? [])
                ->bcc($options['bcc'] ?? ['billing@pharmaconnect.com'])
                ->send(new InvoiceToInsurance($emailData, $fullPath));

            // Update invoice status if needed
            if ($invoice->status === 'pending') {
                $invoice->update(['status' => 'sent']);
            }

            Log::info('Invoice sent to insurance', [
                'invoice_id' => $invoice->id,
                'insurance_provider_id' => $insuranceProvider->id,
                'email' => $insuranceProvider->email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending invoice to insurance', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(Invoice $invoice, array $paymentData = []): Invoice
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        Log::info('Invoice marked as paid', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
        ]);

        return $invoice->fresh();
    }

    /**
     * Cancel invoice
     */
    public function cancelInvoice(Invoice $invoice, string $reason = null): Invoice
    {
        if ($invoice->status === 'paid') {
            throw new \Exception('Cannot cancel paid invoice');
        }

        $invoice->update([
            'status' => 'cancelled',
            'notes' => $invoice->notes ? $invoice->notes . "\n\nCancellation reason: " . $reason : "Cancelled: " . $reason,
        ]);

        Log::info('Invoice cancelled', [
            'invoice_id' => $invoice->id,
            'reason' => $reason,
        ]);

        return $invoice->fresh();
    }

    /**
     * Get overdue invoices
     */
    public function getOverdueInvoices()
    {
        return Invoice::where('status', 'pending')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->with(['order.prescription.patient'])
            ->get();
    }

    /**
     * Bulk generate invoices for delivered orders with insurance
     */
    public function bulkGenerateInvoices(array $orderIds): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        DB::beginTransaction();
        try {
            foreach ($orderIds as $orderId) {
                try {
                    $order = Order::with([
                        'prescription.patient.insuranceProvider'
                    ])->findOrFail($orderId);

                    $invoice = $this->createInvoiceFromOrder($order);
                    $results['success'][] = [
                        'order_id' => $orderId,
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                    ];
                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'order_id' => $orderId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();
            
            Log::info('Bulk invoice generation completed', [
                'success_count' => count($results['success']),
                'failed_count' => count($results['failed']),
            ]);

            return $results;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk invoice generation failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Bulk send invoices to insurance companies
     */
    public function bulkSendInvoices(array $invoiceIds, array $options = []): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($invoiceIds as $invoiceId) {
            try {
                $invoice = Invoice::findOrFail($invoiceId);
                $this->sendInvoiceToInsurance($invoice, $options);
                
                $results['success'][] = [
                    'invoice_id' => $invoiceId,
                    'invoice_number' => $invoice->invoice_number,
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'invoice_id' => $invoiceId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Bulk invoice sending completed', [
            'success_count' => count($results['success']),
            'failed_count' => count($results['failed']),
        ]);

        return $results;
    }
}