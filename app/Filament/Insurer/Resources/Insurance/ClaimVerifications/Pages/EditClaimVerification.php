<?php

namespace App\Filament\Insurer\Resources\Insurance\ClaimVerifications\Pages;

use App\Filament\Insurer\Resources\Insurance\ClaimVerifications\ClaimVerificationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClaimVerification extends EditRecord
{
    protected static string $resource = ClaimVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
