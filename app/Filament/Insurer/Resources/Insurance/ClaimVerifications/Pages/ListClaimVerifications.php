<?php

namespace App\Filament\Insurer\Resources\Insurance\ClaimVerifications\Pages;

use App\Filament\Insurer\Resources\Insurance\ClaimVerifications\ClaimVerificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClaimVerifications extends ListRecords
{
    protected static string $resource = ClaimVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
