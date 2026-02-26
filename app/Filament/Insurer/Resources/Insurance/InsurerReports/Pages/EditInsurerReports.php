<?php

namespace App\Filament\Insurer\Resources\Insurance\InsurerReports\Pages;

use App\Filament\Insurer\Resources\Insurance\InsurerReports\InsurerReportsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInsurerReports extends EditRecord
{
    protected static string $resource = InsurerReportsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->icon('heroicon-o-trash'),
        ];
    }
}
