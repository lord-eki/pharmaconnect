<?php

namespace App\Services;

use App\Models\ClaimForm;
use App\Models\InsuranceProvider;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class ClaimFormService
{
    /**
     * Generate PDF for claim form with insurer branding
     */
    public function generateClaimFormPDF(ClaimForm $claimForm): string
    {
        $insuranceProvider = $claimForm->insuranceProvider;
        
        // Prepare data for PDF
        $data = [
            'claimForm' => $claimForm,
            'prescription' => $claimForm->prescription->load('items.medicine'),
            'patient' => $claimForm->patient,
            'physician' => $claimForm->physician,
            'insuranceProvider' => $insuranceProvider,
            'formTemplate' => $insuranceProvider->getFormTemplate(),
            'logoUrl' => $insuranceProvider->getLogoUrl(),
            'generatedAt' => now(),
        ];

        // Generate PDF with custom view based on insurer
        $viewName = $this->getViewForInsurer($insuranceProvider);
        
        $pdf = Pdf::loadView($viewName, $data)
            ->setPaper('a4')
            ->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        // Save PDF to storage
        $fileName = "claim_form_{$claimForm->form_number}.pdf";
        $filePath = "claim-forms/{$fileName}";
        
        Storage::put($filePath, $pdf->output());

        return $filePath;
    }

    /**
     * Get the appropriate view for the insurer's claim form
     */
    protected function getViewForInsurer(InsuranceProvider $insurer): string
    {
        // Check if insurer has a custom view
        $customView = "claim-forms.insurers." . \Str::slug($insurer->company_name);
        
        if (View::exists($customView)) {
            return $customView;
        }

        // Return default view
        return 'claim-forms.default';
    }

    /**
     * Validate claim form data against insurer requirements
     */
    public function validateClaimFormData(array $data, InsuranceProvider $insurer): array
    {
        $errors = [];
        $requiredFields = $insurer->getRequiredFields();

        foreach ($requiredFields as $field) {
            if (!isset($data['form_data'][$field]) || empty($data['form_data'][$field])) {
                $errors[$field] = "The {$field} field is required by {$insurer->company_name}";
            }
        }

        return $errors;
    }

    /**
     * Send claim form to insurer (via API or email)
     */
    public function submitToInsurer(ClaimForm $claimForm): bool
    {
        $insurer = $claimForm->insuranceProvider;

        // If insurer has API integration
        if ($insurer->api_endpoint && $insurer->api_key) {
            return $this->submitViaApi($claimForm, $insurer);
        }

        // Otherwise, send via email
        return $this->submitViaEmail($claimForm, $insurer);
    }

    /**
     * Submit claim form via insurer API
     */
    protected function submitViaApi(ClaimForm $claimForm, InsuranceProvider $insurer): bool
    {
        try {
            $response = \Http::withHeaders([
                'Authorization' => 'Bearer ' . decrypt($insurer->api_key),
                'Content-Type' => 'application/json',
            ])->post($insurer->api_endpoint . '/claims', [
                'claim_form_number' => $claimForm->form_number,
                'prescription_number' => $claimForm->prescription->prescription_number,
                'patient_data' => [
                    'policy_number' => $claimForm->form_data['policy_number'] ?? null,
                    'member_id' => $claimForm->form_data['member_id'] ?? null,
                    'name' => "{$claimForm->patient->first_name} {$claimForm->patient->last_name}",
                ],
                'diagnosis' => $claimForm->diagnosis,
                'treatment_notes' => $claimForm->treatment_notes,
                'custom_fields' => $claimForm->form_data,
                'document_url' => $claimForm->document?->getDownloadUrl(),
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            \Log::error('Failed to submit claim form via API', [
                'claim_form_id' => $claimForm->id,
                'insurer_id' => $insurer->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Submit claim form via email
     */
    protected function submitViaEmail(ClaimForm $claimForm, InsuranceProvider $insurer): bool
    {
        try {
            \Mail::to($insurer->email)->send(
                new \App\Mail\ClaimFormSubmission($claimForm)
            );

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to submit claim form via email', [
                'claim_form_id' => $claimForm->id,
                'insurer_id' => $insurer->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
