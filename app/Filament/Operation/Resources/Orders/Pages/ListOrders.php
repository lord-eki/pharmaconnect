<?php

namespace App\Filament\Operation\Resources\Orders\Pages;

use App\Filament\Operation\Resources\Orders\OrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Orders')
                ->badge(fn () => \App\Models\Order::count()),

            'pending_review' => Tab::make('Pending Review')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending_review'))
                ->badge(fn () => \App\Models\Order::where('status', 'pending_review')->count())
                ->badgeColor('warning'),

            'sent_to_supplier' => Tab::make('Sent to Supplier')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'sent_to_supplier'))
                ->badge(fn () => \App\Models\Order::where('status', 'sent_to_supplier')->count())
                ->badgeColor('info'),

            'confirmed' => Tab::make('Confirmed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'confirmed'))
                ->badge(fn () => \App\Models\Order::where('status', 'confirmed')->count())
                ->badgeColor('primary'),

            'processing' => Tab::make('Processing')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['processing', 'shipped']))
                ->badge(fn () => \App\Models\Order::whereIn('status', ['processing', 'shipped'])->count())
                ->badgeColor('primary'),

            'delivered' => Tab::make('Delivered')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'delivered'))
                ->badge(fn () => \App\Models\Order::where('status', 'delivered')->count())
                ->badgeColor('success'),

            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled'))
                ->badge(fn () => \App\Models\Order::where('status', 'cancelled')->count())
                ->badgeColor('danger'),
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return 'pending_review';
    }
}
