<?php

namespace App\Filament\Physician\Resources\Physician\Commissions\Pages;

use App\Filament\Physician\Resources\Physician\Commissions\CommissionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCommission extends EditRecord
{
    protected static string $resource = CommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
