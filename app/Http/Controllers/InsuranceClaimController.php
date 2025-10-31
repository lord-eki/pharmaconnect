<?php

namespace App\Http\Controllers;

use App\Models\InsuranceClaim;
use App\Services\InsuranceClaimPDFService;
use Illuminate\Http\Request;

class InsuranceClaimController extends Controller
{
     /**
     * View claim form PDF in browser
     */
    public function viewPDF(InsuranceClaim $claim)
    {
        // Authorization check
        $this->authorize('view', $claim);
        
        return InsuranceClaimPDFService::stream($claim);
    }

    /**
     * Download claim form PDF
     */
    public function downloadPDF(InsuranceClaim $claim)
    {
        // Authorization check
        $this->authorize('view', $claim);
        
        return InsuranceClaimPDFService::download($claim);
    }

    /**
     * Email claim form
     */
    public function emailPDF(Request $request, InsuranceClaim $claim)
    {
        // Authorization check
        $this->authorize('view', $claim);
        
        $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string',
        ]);

        try {
            // \Mail::to($request->email)->send(
            //     new \App\Mail\InsuranceClaimFormMail($claim, $request->message)
            // );

            return response()->json([
                'success' => true,
                'message' => 'Claim form sent successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send claim form email', [
                'claim_id' => $claim->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send email',
            ], 500);
        }
    }
}
