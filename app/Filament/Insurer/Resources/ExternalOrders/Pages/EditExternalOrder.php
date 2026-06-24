<?php

namespace App\Filament\Insurer\Resources\ExternalOrders\Pages;

use App\Filament\Insurer\Resources\ExternalOrders\ExternalOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExternalOrder extends EditRecord
{
    protected static string $resource = ExternalOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->icon('heroicon-o-trash')->label('Delete Order'),
        ];
    }


   
}
