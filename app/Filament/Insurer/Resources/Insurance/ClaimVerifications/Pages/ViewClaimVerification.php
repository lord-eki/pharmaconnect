<?php

namespace App\Filament\Insurer\Resources\Insurance\ClaimVerifications\Pages;

use App\Filament\Insurer\Resources\Insurance\ClaimVerifications\ClaimVerificationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewClaimVerification extends ViewRecord
{
    protected static string $resource = ClaimVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
