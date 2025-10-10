<?php

namespace App\Filament\Supplier\Resources\Supplier\Quotations\Pages;

use App\Filament\Supplier\Resources\Supplier\Quotations\QuotationResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListQuotations extends ListRecords
{
    protected static string $resource = QuotationResource::class;

     protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->dispatch('refresh')),
        ];
    }

    public function getTabs(): array
    {
        $supplierId = Auth::user()->userProfile->id ?? null;

        return [
            'pending' => Tab::make('Pending Response')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', 'pending')
                    ->where('valid_until', '>', now())
                )
                ->badge(fn () => \App\Models\Quotation::whereHas('items', function ($q) use ($supplierId) {
                    $q->where('supplier_id', $supplierId);
                })
                ->where('status', 'pending')
                ->where('valid_until', '>', now())
                ->count())
                ->badgeColor('warning')
                ->icon('heroicon-o-clock'),

            'sent' => Tab::make('Submitted')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'sent'))
                ->badge(fn () => \App\Models\Quotation::whereHas('items', function ($q) use ($supplierId) {
                    $q->where('supplier_id', $supplierId);
                })
                ->where('status', 'sent')
                ->count())
                ->badgeColor('info')
                ->icon('heroicon-o-paper-airplane'),

            'accepted' => Tab::make('Accepted')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'accepted'))
                ->badge(fn () => \App\Models\Quotation::whereHas('items', function ($q) use ($supplierId) {
                    $q->where('supplier_id', $supplierId);
                })
                ->where('status', 'accepted')
                ->count())
                ->badgeColor('success')
                ->icon('heroicon-o-check-circle'),

            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected'))
                ->badge(fn () => \App\Models\Quotation::whereHas('items', function ($q) use ($supplierId) {
                    $q->where('supplier_id', $supplierId);
                })
                ->where('status', 'rejected')
                ->count())
                ->badgeColor('danger')
                ->icon('heroicon-o-x-circle'),

            'expired' => Tab::make('Expired')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where(function ($q) {
                        $q->where('status', 'expired')
                          ->orWhere(function ($q2) {
                              $q2->where('status', 'pending')
                                 ->where('valid_until', '<=', now());
                          });
                    })
                )
                ->badge(fn () => \App\Models\Quotation::whereHas('items', function ($q) use ($supplierId) {
                    $q->where('supplier_id', $supplierId);
                })
                ->where(function ($q) {
                    $q->where('status', 'expired')
                      ->orWhere(function ($q2) {
                          $q2->where('status', 'pending')
                             ->where('valid_until', '<=', now());
                      });
                })
                ->count())
                ->badgeColor('gray')
                ->icon('heroicon-o-archive-box-x-mark'),

            'all' => Tab::make('All Requests')
                ->badge(fn () => \App\Models\Quotation::whereHas('items', function ($q) use ($supplierId) {
                    $q->where('supplier_id', $supplierId);
                })
                ->count())
                ->icon('heroicon-o-queue-list'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // QuotationResource\Widgets\QuotationStatsWidget::class,
        ];
    }
}
