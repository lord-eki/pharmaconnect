<?php

namespace App\Filament\Physician\Resources\Physician\Commissions\Pages;

use App\Filament\Physician\Resources\Physician\Commissions\CommissionResource;
use App\Filament\Physician\Resources\Physician\Commissions\Widgets\Physician\CommissionStatsWidget;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListCommissions extends ListRecords
{

    protected static string $resource = CommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_statement')
                ->label('Download Statement')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                // ->url(route('commissions.statement', ['physician' => Auth::id()]))
                ->openUrlInNewTab(),
        ];
    }

    public function getTabs(): array
    {
        $physicianId = Auth::id();
        
        return [
            'all' => Tab::make('All Commissions')
                ->badge(fn () => static::getResource()::getModel()::where('physician_id', $physicianId)->count()),
            
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => static::getResource()::getModel()::where('physician_id', $physicianId)
                    ->where('status', 'pending')->count())
                ->badgeColor('warning'),
            
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved'))
                ->badge(fn () => static::getResource()::getModel()::where('physician_id', $physicianId)
                    ->where('status', 'approved')->count())
                ->badgeColor('info'),
            
            'paid' => Tab::make('Paid')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paid'))
                ->badge(fn () => static::getResource()::getModel()::where('physician_id', $physicianId)
                    ->where('status', 'paid')->count())
                ->badgeColor('success'),
            
            'this_month' => Tab::make('This Month')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year)
                )
                ->badge(fn () => static::getResource()::getModel()::where('physician_id', $physicianId)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
        CommissionStatsWidget::class
        ];
    }
}
