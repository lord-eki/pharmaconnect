<?php

namespace App\Filament\Resources\Auditlogs\Pages;

use App\Filament\Resources\Auditlogs\AuditlogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAuditlog extends EditRecord
{
    protected static string $resource = AuditlogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
