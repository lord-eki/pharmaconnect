<?php

namespace App\Filament\Resources\Documents\Widgets;

use App\Models\Document;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentStatsOverview extends StatsOverviewWidget
{
  protected function getStats(): array
    {
        $totalDocs = Document::count();
        $pendingDocs = Document::where('verification_status', 'pending')->count();
        $verifiedDocs = Document::where('verification_status', 'verified')->count();
        $rejectedDocs = Document::where('verification_status', 'rejected')->count();
        $lockedDocs = Document::where('is_locked', true)->count();
        $uploadedToday = Document::whereDate('uploaded_at', today())->count();

        return [
            Stat::make('Total Documents', number_format($totalDocs))
                ->description('All documents')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('primary')
                ->chart($this->getLastSevenDaysData()),

            Stat::make('Pending Review', number_format($pendingDocs))
                ->description(($totalDocs > 0 ? round(($pendingDocs / $totalDocs) * 100, 1) : 0) . '% of total')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning')
                ->chart($this->getPendingTrendData()),

            Stat::make('Verified', number_format($verifiedDocs))
                ->description('Approved documents')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->chart($this->getVerifiedTrendData()),

            Stat::make('Rejected', number_format($rejectedDocs))
                ->description('Rejected documents')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make('Locked', number_format($lockedDocs))
                ->description('Protected documents')
                ->descriptionIcon('heroicon-o-lock-closed')
                ->color('warning'),

            Stat::make('Today', number_format($uploadedToday))
                ->description('Uploaded today')
                ->descriptionIcon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->chart($this->getTodayHourlyData()),
        ];
    }

    private function getLastSevenDaysData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = Document::whereDate('uploaded_at', $date)->count();
            $data[] = $count;
        }
        return $data;
    }

    private function getPendingTrendData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = Document::where('verification_status', 'pending')
                ->whereDate('created_at', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }

    private function getVerifiedTrendData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = Document::whereDate('verified_at', $date)->count();
            $data[] = $count;
        }
        return $data;
    }

    private function getTodayHourlyData(): array
    {
        $currentHour = now()->hour;
        $data = [];
        
        for ($i = max(0, $currentHour - 6); $i <= $currentHour; $i++) {
            $count = Document::whereDate('uploaded_at', today())
                ->whereTime('uploaded_at', '>=', str_pad($i, 2, '0', STR_PAD_LEFT) . ':00:00')
                ->whereTime('uploaded_at', '<', str_pad($i + 1, 2, '0', STR_PAD_LEFT) . ':00:00')
                ->count();
            $data[] = $count;
        }
        
        return $data;
    }
}
