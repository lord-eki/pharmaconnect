<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OrderReportService
{
    /**
     * Clean and encode text for PDF generation
     */
    protected function cleanText($text): string
    {
        if (empty($text)) {
            return '';
        }
        
        // Convert to UTF-8 if not already
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }
        
        // Remove any invalid UTF-8 characters
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        // Remove control characters except newlines and tabs
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        
        return $text;
    }
    
    /**
     * Prepare order data for PDF generation (full details)
     */
    protected function prepareOrderData(Order $order): Order
    {
        // Load relationships
        $order->load([
            'prescription.patient',
            'prescription.physician',
            'supplier',
            'items.medicine',
            'delivery'
        ]);
        
        // Clean text fields that might have encoding issues
        if ($order->notes) {
            $order->notes = $this->cleanText($order->notes);
        }
        
        // Clean supplier data
        if ($order->supplier) {
            $order->supplier->company_name = $this->cleanText($order->supplier->company_name);
            $order->supplier->contact_person = $this->cleanText($order->supplier->contact_person ?? '');
            $order->supplier->address = $this->cleanText($order->supplier->address ?? '');
        }
        
        // Clean patient data
        if ($order->prescription && $order->prescription->patient) {
            $patient = $order->prescription->patient;
            $patient->first_name = $this->cleanText($patient->first_name ?? '');
            $patient->last_name = $this->cleanText($patient->last_name ?? '');
            $patient->address = $this->cleanText($patient->address ?? '');
        }
        
        // Clean physician data
        if ($order->prescription && $order->prescription->physician) {
            $physician = $order->prescription->physician;
            $physician->first_name = $this->cleanText($physician->first_name ?? '');
            $physician->last_name = $this->cleanText($physician->last_name ?? '');
        }
        
        // Clean items data
        foreach ($order->items as $item) {
            if ($item->medicine) {
                $item->medicine->name = $this->cleanText($item->medicine->name);
                if ($item->medicine->sku) {
                    $item->medicine->sku = $this->cleanText($item->medicine->sku);
                }
            }
        }
        
        return $order;
    }

    /**
     * Prepare order data for supplier PDF 
     */
    protected function prepareSupplierOrderData(Order $order): Order
    {
        // Load only supplier-relevant relationships
        $order->load([
            'supplier',
            'items.medicine',
            'delivery'
        ]);
        
        // Clean text fields
        if ($order->notes) {
            $order->notes = $this->cleanText($order->notes);
        }
        
        // Clean supplier data
        if ($order->supplier) {
            $order->supplier->company_name = $this->cleanText($order->supplier->company_name);
            $order->supplier->contact_person = $this->cleanText($order->supplier->contact_person ?? '');
            $order->supplier->address = $this->cleanText($order->supplier->address ?? '');
        }
        
        // Clean items data
        foreach ($order->items as $item) {
            if ($item->medicine) {
                $item->medicine->name = $this->cleanText($item->medicine->name);
                if ($item->medicine->sku) {
                    $item->medicine->sku = $this->cleanText($item->medicine->sku);
                }
            }
        }
        
        return $order;
    }

    /**
     * Generate LPO PDF for a single order (Operations)
     */
    public function generateLPO(Order $order): string
    {
        try {
            $order = $this->prepareOrderData($order);
            
            $pdf = Pdf::loadView('reports.lpo', [
                'order' => $order
            ]);

            $pdf->setPaper('a4', 'portrait');
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', false);
            
            $fileName = "LPO_" . preg_replace('/[^A-Za-z0-9_-]/', '_', $order->order_number) . "_" . time() . ".pdf";
            
            $path = "reports/lpo/{$fileName}";
            Storage::disk('public')->put($path, $pdf->output());
            
            return $path;
        } catch (\Exception $e) {
            \Log::error('Error generating LPO PDF', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Generate LPO PDF for supplier 
     */
    public function generateSupplierLPO(Order $order): string
    {
        try {
            $order = $this->prepareSupplierOrderData($order);
            
            $pdf = Pdf::loadView('reports.supplier-lpo', [
                'order' => $order
            ]);

            $pdf->setPaper('a4', 'portrait');
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', false);
            
            $fileName = "LPO_" . preg_replace('/[^A-Za-z0-9_-]/', '_', $order->order_number) . "_" . time() . ".pdf";
            
            $path = "reports/lpo/supplier/{$fileName}";
            Storage::disk('public')->put($path, $pdf->output());
            
            return $path;
        } catch (\Exception $e) {
            \Log::error('Error generating Supplier LPO PDF', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Download LPO PDF (Operations)
     */
    public function downloadLPO(Order $order): \Illuminate\Http\Response
    {
        try {
            $order = $this->prepareOrderData($order);
            
            $pdf = Pdf::loadView('reports.lpo', [
                'order' => $order
            ]);

            $pdf->setPaper('a4', 'portrait');
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', false);
            
            $fileName = "LPO_" . preg_replace('/[^A-Za-z0-9_-]/', '_', $order->order_number) . ".pdf";
            
            return $pdf->download($fileName);
        } catch (\Exception $e) {
            \Log::error('Error downloading LPO PDF', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Download LPO PDF for supplier 
     */
    public function downloadSupplierLPO(Order $order): \Illuminate\Http\Response
    {
        try {
            $order = $this->prepareSupplierOrderData($order);
            
            $pdf = Pdf::loadView('reports.supplier-lpo', [
                'order' => $order
            ]);

            $pdf->setPaper('a4', 'portrait');
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', false);
            
            $fileName = "LPO_" . preg_replace('/[^A-Za-z0-9_-]/', '_', $order->order_number) . ".pdf";
            
            return $pdf->download($fileName);
        } catch (\Exception $e) {
            \Log::error('Error downloading Supplier LPO PDF', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Generate bulk LPO report for multiple orders
     */
    public function generateBulkLPO(array $orderIds): string
    {
        try {
            $orders = Order::with([
                'prescription.patient',
                'prescription.physician',
                'supplier',
                'items.medicine',
                'delivery'
            ])->whereIn('id', $orderIds)->get();

            foreach ($orders as $order) {
                $this->prepareOrderData($order);
            }

            $pdf = Pdf::loadView('reports.bulk-lpo', [
                'orders' => $orders,
                'generatedAt' => now()
            ]);

            $pdf->setPaper('a4', 'portrait');
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', false);
            
            $fileName = "Bulk_LPO_" . now()->format('Y-m-d_His') . ".pdf";
            
            $path = "reports/lpo/{$fileName}";
            Storage::disk('public')->put($path, $pdf->output());
            
            return $path;
        } catch (\Exception $e) {
            Log::error('Error generating bulk LPO PDF', [
                'order_ids' => $orderIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Download bulk LPO report
     */
    public function downloadBulkLPO(array $orderIds): \Illuminate\Http\Response
    {
        try {
            $orders = Order::with([
                'prescription.patient',
                'prescription.physician',
                'supplier',
                'items.medicine',
                'delivery'
            ])->whereIn('id', $orderIds)->get();

            foreach ($orders as $order) {
                $this->prepareOrderData($order);
            }

            $pdf = Pdf::loadView('reports.bulk-lpo', [
                'orders' => $orders,
                'generatedAt' => now()
            ]);

            $pdf->setPaper('a4', 'portrait');
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', false);
            
            $fileName = "Bulk_LPO_" . now()->format('Y-m-d_His') . ".pdf";
            
            return $pdf->download($fileName);
        } catch (\Exception $e) {
            \Log::error('Error downloading bulk LPO PDF', [
                'order_ids' => $orderIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}