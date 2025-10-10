<?php

namespace App\Filament\Physician\Resources\Physician\Orders\Pages;

use App\Filament\Physician\Resources\Physician\Orders\OrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;
}
