<?php

namespace App\Services;

use App\Models\ClaimForm;
use App\Models\InsuranceFormTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;

class InsuranceFormGeneratorService
{
    /**
     * Generate claim form based on insurance provider template
     */
    public function generateClaimForm(ClaimForm $claimForm): ?string
    {
        $template = $claimForm->insuranceProvider->activeFormTemplate;

        if (!$template) {
            Log::warning('No active template found for insurance provider', [
                'provider_id' => $claimForm->insurance_provider_id,
                'claim_form_id' => $claimForm->id,
            ]);
            return null;
        }

        try {
            $documentPath = match ($template->template_type) {
                'pdf' => $this->generatePdfForm($claimForm, $template),
                'html' => $this->generateHtmlPdfForm($claimForm, $template),
                'blade' => $this->generateBladePdfForm($claimForm, $template),
                'api' => $this->submitViaApi($claimForm, $template),
                default => throw new \Exception("Unsupported template type: {$template->template_type}")
            };

            // Update claim form with generated document
            $claimForm->update([
                'generated_document_path' => $documentPath,
                'template_used' => $template->template_name . ' v' . $template->version,
                'generated_at' => now(),
            ]);

            return $documentPath;

        } catch (\Exception $e) {
            Log::error('Error generating insurance claim form', [
                'claim_form_id' => $claimForm->id,
                'template_id' => $template->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Fill PDF template with data (for fillable PDFs)
     */
    protected function generatePdfForm(ClaimForm $claimForm, InsuranceFormTemplate $template): string
    {
        $templatePath = storage_path('app/' . $template->template_path);
        
        if (!file_exists($templatePath)) {
            throw new \Exception("Template file not found: {$templatePath}");
        }

        $data = $this->prepareFormData($claimForm, $template);
        
        // Use FPDI to fill PDF form fields
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($templatePath);
        
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $pdf->AddPage();
            $tplIdx = $pdf->importPage($pageNo);
            $pdf->useTemplate($tplIdx);
            
            // Fill form fields based on mapping
            $this->fillPdfFields($pdf, $data, $template->getFieldMappings(), $pageNo);
        }

        $fileName = "claim_form_{$claimForm->id}_{$claimForm->form_number}.pdf";
        $outputPath = "claim-forms/generated/{$fileName}";
        
        Storage::put($outputPath, $pdf->Output('S'));
        
        return $outputPath;
    }

    /**
     * Generate PDF from HTML template
     */
    protected function generateHtmlPdfForm(ClaimForm $claimForm, InsuranceFormTemplate $template): string
    {
        $data = $this->prepareFormData($claimForm, $template);
        
        $htmlTemplate = Storage::get($template->template_path);
        
        // Replace placeholders with actual data
        $html = $this->replacePlaceholders($htmlTemplate, $data);
        
        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10);
        
        $fileName = "claim_form_{$claimForm->id}_{$claimForm->form_number}.pdf";
        $outputPath = "claim-forms/generated/{$fileName}";
        
        Storage::put($outputPath, $pdf->output());
        
        return $outputPath;
    }

    /**
     * Generate PDF from Blade template
     */
    protected function generateBladePdfForm(ClaimForm $claimForm, InsuranceFormTemplate $template): string
    {
        $data = $this->prepareFormData($claimForm, $template);
        
        // Extract blade view name from template_path (e.g., 'insurance-forms.aaa-insurance')
        $viewName = str_replace('.blade.php', '', $template->template_path);
        
        $pdf = Pdf::loadView($viewName, $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10);
        
        $fileName = "claim_form_{$claimForm->id}_{$claimForm->form_number}.pdf";
        $outputPath = "claim-forms/generated/{$fileName}";
        
        Storage::put($outputPath, $pdf->output());
        
        return $outputPath;
    }

    /**
     * Submit via API (if insurance provider supports it)
     */
    protected function submitViaApi(ClaimForm $claimForm, InsuranceFormTemplate $template): ?string
    {
        $apiConfig = $template->template_config['api_config'] ?? [];
        $data = $this->prepareFormData($claimForm, $template);
        
        // Implementation depends on specific API
        // This is a placeholder for API submission logic
        
        Log::info('API submission initiated', [
            'claim_form_id' => $claimForm->id,
            'provider' => $claimForm->insuranceProvider->company_name,
        ]);
        
        // Return confirmation document path after successful API submission
        return null;
    }

    /**
     * Prepare form data from claim form
     */
    protected function prepareFormData(ClaimForm $claimForm, InsuranceFormTemplate $template): array
    {
        $claimForm->load([
            'prescription.items.medicine',
            'prescription.patient',
            'prescription.physician',
            'patient',
            'physician',
            'insuranceProvider',
        ]);

        $prescription = $claimForm->prescription;
        $patient = $claimForm->patient;
        $physician = $claimForm->physician;

        return [
            // Form identification
            'form_number' => $claimForm->form_number,
            'submission_date' => now()->format('d/m/Y'),
            
            // Patient information
            'patient_name' => $patient->full_name,
            'patient_number' => $patient->patient_number,
            'patient_dob' => $patient->date_of_birth->format('d/m/Y'),
            'patient_gender' => ucfirst($patient->gender),
            'patient_phone' => $patient->phone,
            'patient_email' => $patient->email,
            'patient_address' => $patient->address,
            'patient_county' => $patient->county,
            'patient_city' => $patient->city,
            
            // Insurance information
            'insurance_provider' => $claimForm->insuranceProvider->company_name,
            'policy_number' => $patient->insurance_number,
            'insurance_phone' => $claimForm->insuranceProvider->phone,
            'insurance_email' => $claimForm->insuranceProvider->email,
            
            // Physician information
            'physician_name' => $physician->name,
            'physician_license' => $physician->license_number ?? '',
            'physician_phone' => $physician->phone ?? '',
            'physician_email' => $physician->email,
            
            // Prescription information
            'prescription_number' => $prescription->prescription_number,
            'prescription_date' => $prescription->prescribed_at->format('d/m/Y'),
            'diagnosis' => $claimForm->diagnosis,
            'treatment_notes' => $claimForm->treatment_notes,
            
            // Medicines list
            'medicines' => $prescription->items->map(function ($item) {
                return [
                    'name' => $item->medicine->generic_name,
                    'brand' => $item->medicine->brand_name,
                    'strength' => $item->medicine->strength,
                    'quantity' => $item->quantity,
                    'dosage' => $item->frequency,
                    'duration' => $item->duration_days,
                    'instructions' => $item->dosage_instructions,
                    'unit_price' => number_format($item->unit_price, 2),
                    'total_price' => number_format($item->total_price, 2),
                ];
            })->toArray(),
            
            // Financial information
            'total_amount' => number_format($prescription->total_amount, 2),
            'currency' => 'KES',
            
            // Custom fields from template config
            'custom_fields' => $this->mapCustomFields($claimForm, $template),
        ];
    }

    /**
     * Map custom fields based on template configuration
     */
    protected function mapCustomFields(ClaimForm $claimForm, InsuranceFormTemplate $template): array
    {
        $customConfig = $template->template_config['custom_fields'] ?? [];
        $customData = [];
        
        foreach ($customConfig as $fieldKey => $fieldMapping) {
            // Use dot notation to access nested properties
            $customData[$fieldKey] = data_get($claimForm, $fieldMapping);
        }
        
        return $customData;
    }

    /**
     * Replace placeholders in HTML template
     */
    protected function replacePlaceholders(string $html, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Handle nested arrays (like medicines list)
                $html = $this->replaceArrayPlaceholders($html, $key, $value);
            } else {
                $html = str_replace("{{{$key}}}", (string)$value, $html);
            }
        }
        
        return $html;
    }

    /**
     * Replace array placeholders (for repeating sections like medicine lists)
     */
    protected function replaceArrayPlaceholders(string $html, string $key, array $items): string
    {
        // Look for loops like {{medicines_start}}...{{medicines_end}}
        $pattern = "/{{" . $key . "_start}}(.*?){{" . $key . "_end}}/s";
        
        if (preg_match($pattern, $html, $matches)) {
            $template = $matches[1];
            $output = '';
            
            foreach ($items as $index => $item) {
                $row = $template;
                foreach ($item as $itemKey => $itemValue) {
                    $row = str_replace("{{{$key}.{$itemKey}}}", (string)$itemValue, $row);
                    $row = str_replace("{{{$key}.index}}", (string)($index + 1), $row);
                }
                $output .= $row;
            }
            
            $html = preg_replace($pattern, $output, $html);
        }
        
        return $html;
    }

    /**
     * Fill PDF form fields
     */
    protected function fillPdfFields($pdf, array $data, array $fieldMappings, int $pageNo): void
    {
        foreach ($fieldMappings as $fieldName => $dataPath) {
            if (isset($fieldMappings['page']) && $fieldMappings['page'] !== $pageNo) {
                continue;
            }
            
            $value = data_get($data, $dataPath);
            
            // Set font
            $pdf->SetFont('Helvetica', '', 10);
            
            // Position and write text (coordinates from field mappings)
            if (isset($fieldMappings[$fieldName . '_x']) && isset($fieldMappings[$fieldName . '_y'])) {
                $x = $fieldMappings[$fieldName . '_x'];
                $y = $fieldMappings[$fieldName . '_y'];
                $pdf->SetXY($x, $y);
                $pdf->Write(0, (string)$value);
            }
        }
    }
}