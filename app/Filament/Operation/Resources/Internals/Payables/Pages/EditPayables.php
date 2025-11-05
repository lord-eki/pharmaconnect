<?php

namespace App\Filament\Operation\Resources\Internals\Payables\Pages;

use App\Filament\Operation\Resources\Internals\Payables\PayablesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPayables extends EditRecord
{
    protected static string $resource = PayablesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
