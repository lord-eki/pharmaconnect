<?php

namespace App\Filament\Physician\Resources\Physician\Commissions\Pages;

use App\Filament\Physician\Resources\Physician\Commissions\CommissionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommissions extends ListRecords
{
    protected static string $resource = CommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
