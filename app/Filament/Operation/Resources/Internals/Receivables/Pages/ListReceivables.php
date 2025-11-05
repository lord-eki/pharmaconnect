<?php

namespace App\Filament\Operation\Resources\Internals\Receivables\Pages;

use App\Filament\Operation\Resources\Internals\Receivables\ReceivablesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReceivables extends ListRecords
{
    protected static string $resource = ReceivablesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
