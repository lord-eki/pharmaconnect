<?php

namespace App\Filament\Operation\Resources\Internals\Payables\Pages;

use App\Filament\Operation\Resources\Internals\Payables\PayablesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayables extends ListRecords
{
    protected static string $resource = PayablesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
