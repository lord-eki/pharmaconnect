<?php

namespace App\Filament\Operation\Resources\Documents\Pages;

use App\Filament\Operation\Resources\Documents\DocumentResource;
use App\Filament\Operation\Resources\Documents\Widgets\DocumentStatsOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-clipboard-document-check')->label('Upload Document'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DocumentStatsOverview::class,
        ];
    }
}
