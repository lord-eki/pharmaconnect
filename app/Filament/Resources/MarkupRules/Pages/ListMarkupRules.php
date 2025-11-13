<?php

namespace App\Filament\Resources\MarkupRules\Pages;

use App\Filament\Resources\MarkupRules\MarkupRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMarkupRules extends ListRecords
{
    protected static string $resource = MarkupRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
