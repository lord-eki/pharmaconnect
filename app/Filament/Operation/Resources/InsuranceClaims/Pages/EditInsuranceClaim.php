<?php

namespace App\Filament\Operation\Resources\InsuranceClaims\Pages;

use App\Filament\Operation\Resources\InsuranceClaims\InsuranceClaimResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInsuranceClaim extends EditRecord
{
    protected static string $resource = InsuranceClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
