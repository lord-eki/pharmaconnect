<?php

namespace App\Http\Controllers;

use App\Models\InsuranceClaim;
use Illuminate\Http\Request;

class InsuranceClaimController extends Controller
{
    protected InsuranceClaimPdfGenerator $pdfGenerator;

    public function __construct(InsuranceClaimPdfGenerator $pdfGenerator)
    {
        $this->pdfGenerator = $pdfGenerator;
    }

    /**
     * Download claim PDF
     */

    public function downloadClaimPdf(InsuranceClaim $claim)
    {
        return $this->pdfGenerator->download($claim);
    }

    /**
     * View claim PDF in browser
     */
    public function previewPdf(InsuranceClaim $claim)
    {
        return $this->pdfGenerator->stream($claim);
    }

    /**
     * Generate  and store claim PDF
     */

    public function generatePdf(InsuranceClaim $claim)
    {
        $filename = $this->pdfGenerator->generate($claim);

        return response()->json([
            'success' => true,
            'filename' => $filename,
            'message' => 'PDF generated successfully.'
        ]);
    }
}
