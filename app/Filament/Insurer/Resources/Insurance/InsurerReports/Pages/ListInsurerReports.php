<?php

namespace App\Filament\Insurer\Resources\Insurance\InsurerReports\Pages;

use App\Filament\Insurer\Resources\Insurance\InsurerReports\InsurerReportsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInsurerReports extends ListRecords
{
    protected static string $resource = InsurerReportsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
