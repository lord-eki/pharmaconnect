<?php

namespace App\Filament\Resources\Auditlogs\Pages;

use App\Filament\Resources\Auditlogs\AuditlogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAuditlog extends CreateRecord
{
    protected static string $resource = AuditlogResource::class;

    public static function canCreate(): bool
    {
        return false;
    }
}
