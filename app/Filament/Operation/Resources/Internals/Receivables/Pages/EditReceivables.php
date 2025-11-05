<?php

namespace App\Filament\Operation\Resources\Internals\Receivables\Pages;

use App\Filament\Operation\Resources\Internals\Receivables\ReceivablesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReceivables extends EditRecord
{
    protected static string $resource = ReceivablesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
