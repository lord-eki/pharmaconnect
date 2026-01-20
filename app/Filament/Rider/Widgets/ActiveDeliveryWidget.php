<?php

namespace App\Filament\Rider\Widgets;

use Filament\Widgets\Widget;

class ActiveDeliveryWidget extends Widget
{
    protected string $view = 'filament.rider.widgets.active-delivery-widget';


    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function getActiveDelivery()
    {
        $rider = auth()->user()->rider;
        
        if (!$rider) {
            return null;
        }

        return $rider->deliveries()
            ->with(['order.prescription', 'order.customer'])
            ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
            ->latest()
            ->first();
    }

    public function getPendingDeliveries()
    {
        $rider = auth()->user()->rider;
        
        if (!$rider) {
            return collect();
        }

        return \App\Models\Delivery::where('status', 'pending')
        
            ->with(['order.prescription', 'order.customer'])
            ->latest()
            ->limit(5)
            ->get();
    }
}
