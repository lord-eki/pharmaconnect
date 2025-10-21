<?php

namespace App\Filament\Supplier\Resources\Supplier\Financials\Pages;

use App\Filament\Supplier\Resources\Supplier\Financials\FinancialResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFinancial extends EditRecord
{
    protected static string $resource = FinancialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
