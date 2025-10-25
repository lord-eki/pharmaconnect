<?php

namespace App\Filament\Resources\InsuranceClaims\Pages;

use App\Filament\Resources\InsuranceClaims\InsuranceClaimResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInsuranceClaim extends ViewRecord
{
    protected static string $resource = InsuranceClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
