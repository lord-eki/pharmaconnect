<?php

namespace App\Filament\Insurer\Resources\Insurance\InsurerOrders\Pages;

use App\Filament\Insurer\Resources\Insurance\InsurerOrders\InsurerOrderResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInsurerOrder extends ViewRecord
{
    protected static string $resource = InsurerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
