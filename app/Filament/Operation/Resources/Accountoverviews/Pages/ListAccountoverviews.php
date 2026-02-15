<?php

namespace App\Filament\Operation\Resources\Accountoverviews\Pages;

use App\Filament\Operation\Resources\Accountoverviews\AccountoverviewResource;
use App\Filament\Operation\Resources\Accountoverviews\Widgets\AccountSummaryWidget;
use App\Filament\Operation\Resources\Accountoverviews\Widgets\CategorybreakdownWidget;
use Filament\Actions\CreateAction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListAccountoverviews extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = AccountOverviewResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
           AccountSummaryWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
           CategorybreakdownWidget::class,
        ];
    }
}
