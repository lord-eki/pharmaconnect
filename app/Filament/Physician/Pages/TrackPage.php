<?php

namespace App\Filament\Physician\Pages;

use App\Models\Delivery;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class TrackPage extends Page
{
    // protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    
    protected  string $view = 'filament.physician.pages.track-page';
    
    protected static bool $shouldRegisterNavigation = false;
    
    protected static ?string $slug = 'track-delivery/{record}';
    
    public ?Delivery $record = null;

    public function mount(int | string $record): void
    {
        $this->record = Delivery::with([
            'order.prescription.patient',
            'order.supplier',
            'rider',
            'tracking' => fn($query) => $query->orderBy('recorded_at', 'desc')
        ])->findOrFail($record);
        
        // Authorization check
        if ($this->record->order->prescription->physician_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this delivery.');
        }
    }

    public function getTitle(): string | Htmlable
    {
        return 'Track Delivery: ' . ($this->record?->delivery_number ?? 'Loading...');
    }
    
    public static function getNavigationLabel(): string
    {
        return 'Track Delivery';
    }

    protected function getViewData(): array
    {
        $tracking = $this->record->tracking;
        $latestTracking = $tracking->first();
        
        return [
            'delivery' => $this->record,
            'latestTracking' => $latestTracking,
            'trackingHistory' => $tracking,
            'pickupLat' => $this->record->pickup_latitude ?? -1.2921,
            'pickupLng' => $this->record->pickup_longitude ?? 36.8219,
            'deliveryLat' => $this->record->delivery_latitude ?? -1.2864,
            'deliveryLng' => $this->record->delivery_longitude ?? 36.8172,
            'currentLat' => $latestTracking?->latitude ?? $this->record->pickup_latitude ?? -1.2921,
            'currentLng' => $latestTracking?->longitude ?? $this->record->pickup_longitude ?? 36.8219,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}