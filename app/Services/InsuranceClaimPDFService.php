<?php

namespace App\Services;

use App\Models\InsuranceClaim;
use Barryvdh\DomPDF\Facade\Pdf;

class InsuranceClaimPDFService
{
    /**
     * Generate branded claim form PDF
     */
    public static function generate(InsuranceClaim $claim): \Barryvdh\DomPDF\PDF
    {
        $claim->load([
            'prescription.items.medicine',
            'prescription.physician',
            'prescription.orders.items.medicine',
            'patient',
            'insuranceProvider',
        ]);

        $provider = $claim->insuranceProvider;
        
        // Prepare branding data
        $branding = [
            'logo_url' => $provider->logo_path ? asset('storage/' . $provider->logo_path) : null,
            'header_text' => $provider->header_text ?: "Insurance Claim Form - {$provider->company_name}",
            'footer_text' => $provider->footer_text ?: self::getDefaultFooter($provider),
            'primary_color' => $provider->primary_color ?: '#000000',
            'secondary_color' => $provider->secondary_color ?: '#666666',
        ];

        return PDF::loadView('pdf.insurance-claim', [
            'claim' => $claim,
            'branding' => $branding,
        ])
        ->setPaper('a4', 'portrait')
        ->setOption('margin-top', 10)
        ->setOption('margin-bottom', 10);
    }

    /**
     * Get default footer text
     */
    protected static function getDefaultFooter($provider): string
    {
        $parts = [];
        
        if ($provider->phone) {
            $parts[] = "Tel: {$provider->phone}";
        }
        
        if ($provider->email) {
            $parts[] = "Email: {$provider->email}";
        }
        
        if ($provider->website) {
            $parts[] = "Web: {$provider->website}";
        }
        
        if ($provider->address) {
            $parts[] = $provider->address;
        }

        return implode(' | ', $parts);
    }

    /**
     * Download claim form
     */
    public static function download(InsuranceClaim $claim): \Symfony\Component\HttpFoundation\Response
    {
        $pdf = self::generate($claim);
        $filename = "claim-{$claim->claim_number}.pdf";
        
        return $pdf->download($filename);
    }

    /**
     * Stream claim form (view in browser)
     */
    public static function stream(InsuranceClaim $claim): \Symfony\Component\HttpFoundation\Response
    {
        $pdf = self::generate($claim);
        $filename = "claim-{$claim->claim_number}.pdf";
        
        return $pdf->stream($filename);
    }

    /**
     * Save claim form to storage
     */
    public static function save(InsuranceClaim $claim, string $path = 'insurance-claims'): string
    {
        $pdf = self::generate($claim);
        $filename = "claim-{$claim->claim_number}.pdf";
        $fullPath = storage_path("app/public/{$path}/{$filename}");
        
        // Ensure directory exists
        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }
        
        $pdf->save($fullPath);
        
        return "{$path}/{$filename}";
    }
}