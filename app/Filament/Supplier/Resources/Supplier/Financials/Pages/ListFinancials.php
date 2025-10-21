<?php

namespace App\Filament\Supplier\Resources\Supplier\Financials\Pages;

use App\Filament\Supplier\Resources\Supplier\Financials\FinancialResource;
use App\Filament\Supplier\Resources\Supplier\Financials\Widgets\Supplier\FinancialStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListFinancials extends ListRecords
{
    protected static string $resource = FinancialResource::class;

   public function getTabs(): array
    {
        $supplierId = Auth::user()->userProfile->id ?? null;

        return [
            'all' => Tab::make('All Payments')
                ->badge(fn () => \App\Models\Payment::where('payee_id', $supplierId)->count()),

            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => \App\Models\Payment::where('payee_id', $supplierId)
                    ->where('status', 'pending')
                    ->count())
                ->badgeColor('warning'),

            'processing' => Tab::make('Processing')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'processing'))
                ->badge(fn () => \App\Models\Payment::where('payee_id', $supplierId)
                    ->where('status', 'processing')
                    ->count())
                ->badgeColor('info'),

            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed'))
                ->badge(fn () => \App\Models\Payment::where('payee_id', $supplierId)
                    ->where('status', 'completed')
                    ->count())
                ->badgeColor('success'),

            'failed' => Tab::make('Failed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'failed'))
                ->badge(fn () => \App\Models\Payment::where('payee_id', $supplierId)
                    ->where('status', 'failed')
                    ->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinancialStatsWidget::class
        ];
    }
}
