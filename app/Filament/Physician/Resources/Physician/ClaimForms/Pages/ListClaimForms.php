<?php

namespace App\Filament\Physician\Resources\Physician\ClaimForms\Pages;

use App\Filament\Physician\Resources\Physician\ClaimForms\ClaimFormResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClaimForms extends ListRecords
{
    protected static string $resource = ClaimFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Claim Form'),
        ];
    }
}
