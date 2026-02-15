<?php

namespace App\Filament\Operation\Resources\Accountoverviews\Pages;

use App\Filament\Operation\Resources\Accountoverviews\AccountoverviewResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAccountoverview extends EditRecord
{
    protected static string $resource = AccountoverviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
