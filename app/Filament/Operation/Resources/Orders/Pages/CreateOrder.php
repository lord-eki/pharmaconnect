<?php

namespace App\Filament\Operation\Resources\Orders\Pages;

use App\Filament\Operation\Resources\Orders\OrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;
}
