<?php

namespace App\Filament\Physician\Resources\Documents\Pages;

use App\Filament\Physician\Resources\Documents\DocumentResource;
use App\Filament\Physician\Resources\Documents\Widgets\DocumentStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListDocuments extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DocumentStatsWidget::class
        ];
            
    }
}
