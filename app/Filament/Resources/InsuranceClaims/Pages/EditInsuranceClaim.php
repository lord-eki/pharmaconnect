<?php

namespace App\Filament\Resources\InsuranceClaims\Pages;

use App\Filament\Resources\InsuranceClaims\InsuranceClaimResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditInsuranceClaim extends EditRecord
{
    protected static string $resource = InsuranceClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ViewAction::make(),
            // DeleteAction::make(),
        ];
    }
}
