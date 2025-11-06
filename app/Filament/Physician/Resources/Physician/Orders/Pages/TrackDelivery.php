<?php

namespace App\Filament\Physician\Resources\Physician\Orders\Pages;

use App\Filament\Physician\Resources\Physician\Orders\OrderResource;
use App\Models\Delivery;
use App\Services\DeliveryTrackingService;
use Filament\Resources\Pages\Page;

class TrackDelivery extends Page
{
    protected static string $resource = OrderResource::class;

    protected string $view = 'filament.physician.resources.physician.orders.pages.track-delivery';

    public Delivery $delivery;
    public array $trackingData = [];

    public function mount(int $deliveryId) : void
    {
        $this->delivery = Delivery::with(['order','rider'])->findOrFail($deliveryId);

        $trackingService = app(DeliveryTrackingService::class);
        $this->trackingData = $trackingService->getTrackingSummary($this->delivery);
    }
}
