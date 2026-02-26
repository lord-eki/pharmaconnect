<?php

namespace App\Filament\Resources\Auditlogs\Pages;

use App\Filament\Resources\Auditlogs\AuditlogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAuditlogs extends ListRecords
{
    protected static string $resource = AuditlogResource::class;

  
}
