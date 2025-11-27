<?php

namespace App\Filament\Insurer\Resources\Insurance\InsurerOrders\Pages;

use App\Filament\Insurer\Resources\Insurance\InsurerOrders\InsurerOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInsurerOrder extends EditRecord
{
    protected static string $resource = InsurerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
