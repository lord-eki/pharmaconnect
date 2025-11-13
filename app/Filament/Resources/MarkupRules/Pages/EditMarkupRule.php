<?php

namespace App\Filament\Resources\MarkupRules\Pages;

use App\Filament\Resources\MarkupRules\MarkupRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMarkupRule extends EditRecord
{
    protected static string $resource = MarkupRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
