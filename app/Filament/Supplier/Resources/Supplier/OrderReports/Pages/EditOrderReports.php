<?php

namespace App\Filament\Supplier\Resources\Supplier\OrderReports\Pages;

use App\Filament\Supplier\Resources\Supplier\OrderReports\OrderReportsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrderReports extends EditRecord
{
    protected static string $resource = OrderReportsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
