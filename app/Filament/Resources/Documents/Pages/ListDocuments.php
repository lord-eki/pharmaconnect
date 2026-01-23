<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\Documents\Widgets\DocumentStatsOverview;
use App\Models\Document;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

      protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Upload New Document')->outlined()
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DocumentStatsOverview::class
        ];
    }


    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Documents')
                ->badge(fn () => Document::count()),

            'pending' => Tab::make('Pending Verification')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('verification_status', 'pending'))
                ->badge(fn () => Document::where('verification_status', 'pending')->count())
                ->badgeColor('warning'),

            'verified' => Tab::make('Verified')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('verification_status', 'verified'))
                ->badge(fn () => Document::where('verification_status', 'verified')->count())
                ->badgeColor('success'),

            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('verification_status', 'rejected'))
                ->badge(fn () => Document::where('verification_status', 'rejected')->count())
                ->badgeColor('danger'),

            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active'))
                ->badge(fn () => Document::where('status', 'active')->count()),

            'archived' => Tab::make('Archived')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'archived'))
                ->badge(fn () => Document::where('status', 'archived')->count()),

            'locked' => Tab::make('Locked')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_locked', true))
                ->badge(fn () => Document::where('is_locked', true)->count())
                ->badgeColor('danger'),

            'today' => Tab::make('Uploaded Today')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('uploaded_at', today()))
                ->badge(fn () => Document::whereDate('uploaded_at', today())->count())
                ->badgeColor('info'),
        ];
    }
}
