<?php

namespace App\Services;

use App\Models\InsuranceClaim;
use App\Models\Setting;
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
            'prescription.orders',       
            'externalOrder.items.medicine',
            'externalOrder.orders',       
            'patient',
            'insuranceProvider',
        ]);

        $provider = $claim->insuranceProvider;
        
        // Prepare branding data
        $branding = [
            'logo_url' => $provider->logo ? asset('storage/' . $provider->logo) : null,
            'header_text' => $provider->header_text ?: "Insurance Claim Form - {$provider->company_name}",
            'footer_text' => $provider->footer_text ?: self::getDefaultFooter($provider),
            'primary_color' => $provider->primary_color ?: '#000000',
            'secondary_color' => $provider->secondary_color ?: '#666666',
        ];

        $deliveryFeeItem = null;

        $parentOrders = null;
        if ($claim->prescription) {
            $parentOrders = $claim->prescription->orders;
        } elseif ($claim->externalOrder) {
            $parentOrders = $claim->externalOrder->orders;
        }

        if ($parentOrders) {
            $firstOrder = $parentOrders->sortBy('id')->first();
            if ($firstOrder) {
                $firstOrder->load(['items' => fn ($q) => $q->orderBy('id', 'asc')]);

                $deliveryFeeItem = $firstOrder->items->firstWhere('is_delivery_fee', true);

                if (! $deliveryFeeItem) {
                    $fee = Setting::deliveryFee();
                    $deliveryFeeItem = (object) [
                        'total_price'     => $fee,
                        'is_delivery_fee' => true,
                        'is_synthesised'  => true,
                    ];
                }
            }
        }

        return PDF::loadView('pdf.insurance-claim', [
            'claim'           => $claim,
            'branding'        => $branding,
            'deliveryFeeItem' => $deliveryFeeItem,
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
     * Stream claim form 
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