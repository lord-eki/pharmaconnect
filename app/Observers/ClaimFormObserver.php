<?php

namespace App\Observers;

use App\Models\ClaimForm;
use App\Services\InsuranceFormGeneratorService;
use Illuminate\Support\Facades\Log;

class ClaimFormObserver
{
    protected $formGenerator;

    public function __construct(InsuranceFormGeneratorService $formGenerator)
    {
        $this->formGenerator = $formGenerator;
    }

    /**
     * Handle the ClaimForm "created" event.
     */
    public function created(ClaimForm $claimForm)
    {
        // Only generate for online submissions
        if ($claimForm->submission_type === 'online') {
            try {
                $this->formGenerator->generateClaimForm($claimForm);
                
                Log::info('Insurance claim form generated successfully', [
                    'claim_form_id' => $claimForm->id,
                    'form_number' => $claimForm->form_number,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to generate insurance claim form on creation', [
                    'claim_form_id' => $claimForm->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
