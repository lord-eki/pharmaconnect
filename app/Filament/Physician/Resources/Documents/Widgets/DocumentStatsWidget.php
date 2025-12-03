<?php

namespace App\Filament\Physician\Resources\Documents\Widgets;

use App\Models\Document;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class DocumentStatsWidget extends StatsOverviewWidget
{
   protected function getStats(): array
    {
        $totalDocuments = Document::count();
        $pendingVerification = Document::where('verification_status', 'pending')->count();
        $verifiedDocuments = Document::where('verification_status', 'verified')->count();
        $rejectedDocuments = Document::where('verification_status', 'rejected')->count();
        
        // Documents uploaded this month
        $thisMonth = Document::whereMonth('uploaded_at', now()->month)
            ->whereYear('uploaded_at', now()->year)
            ->count();
        
        // Documents uploaded last month
        $lastMonth = Document::whereMonth('uploaded_at', now()->subMonth()->month)
            ->whereYear('uploaded_at', now()->subMonth()->year)
            ->count();
        
        // Calculate percentage change
        $monthlyChange = $lastMonth > 0 
            ? (($thisMonth - $lastMonth) / $lastMonth) * 100 
            : 0;
        
        // Total storage used
        $totalStorage = Document::sum('file_size');
        $storageFormatted = $this->formatBytes($totalStorage);
        
        // Average document size
        $avgSize = $totalDocuments > 0 ? $totalStorage / $totalDocuments : 0;
        $avgSizeFormatted = $this->formatBytes($avgSize);

        return [
            Stat::make('Total Documents', $totalDocuments)
                ->description($thisMonth . ' uploaded this month')
                ->descriptionIcon($monthlyChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($monthlyChange >= 0 ? 'success' : 'danger')
                ->chart($this->getDocumentTrend()),

            Stat::make('Pending Verification', $pendingVerification)
                ->description($verifiedDocuments . ' verified')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
                // ->url(route('filament.admin.resources.documents.index', [
                //     'tableFilters' => [
                //         'verification_status' => ['value' => 'pending']
                //     ]
                // ])),

            Stat::make('Storage Used', $storageFormatted)
                ->description('Avg: ' . $avgSizeFormatted . ' per document')
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color('primary'),

            Stat::make('By Type', '')
                ->description($this->getDocumentTypeBreakdown())
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
        ];
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    protected function getDocumentTrend(): array
    {
        // Get last 7 days document count
        return Document::selectRaw('DATE(uploaded_at) as date, COUNT(*) as count')
            ->where('uploaded_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
    }

    protected function getDocumentTypeBreakdown(): string
    {
        $types = Document::select('document_type', DB::raw('COUNT(*) as count'))
            ->groupBy('document_type')
            ->orderByDesc('count')
            ->limit(3)
            ->get();

        if ($types->isEmpty()) {
            return 'No documents yet';
        }

        return $types->map(function ($type) {
            return ucwords(str_replace('_', ' ', $type->document_type)) . ': ' . $type->count;
        })->implode(' | ');
    }
}
