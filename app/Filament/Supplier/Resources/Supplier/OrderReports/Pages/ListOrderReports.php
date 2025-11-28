<?php

namespace App\Filament\Supplier\Resources\Supplier\OrderReports\Pages;

use App\Filament\Supplier\Resources\Supplier\OrderReports\OrderReportsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrderReports extends ListRecords
{
    protected static string $resource = OrderReportsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
