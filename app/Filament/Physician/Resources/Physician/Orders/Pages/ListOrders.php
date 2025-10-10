<?php

namespace App\Filament\Physician\Resources\Physician\Orders\Pages;

use App\Filament\Physician\Resources\Physician\Orders\OrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
