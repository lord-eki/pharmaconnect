<?php

namespace App\Filament\Operation\Resources\Documents\Widgets;

use App\Models\Document;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Documents', Document::count())
                ->description('All documents in system')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Pending Verification', Document::where('verification_status', 'pending')->count())
                ->description('Awaiting review')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning')
                ->chart([3, 4, 3, 5, 6, 7, 8, 4]),

            Stat::make('Verified Today', Document::whereDate('verified_at', today())->count())
                ->description('Documents verified today')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->chart([1, 2, 1, 3, 2, 4, 3, 2]),

            Stat::make('Uploaded This Week', Document::whereBetween('uploaded_at', [now()->startOfWeek(), now()->endOfWeek()])->count())
                ->description('New uploads this week')
                ->descriptionIcon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->chart([2, 4, 3, 6, 5, 7, 9, 8]),
        ];
    }
}
