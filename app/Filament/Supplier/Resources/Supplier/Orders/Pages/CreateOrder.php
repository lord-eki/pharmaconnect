<?php

namespace App\Filament\Supplier\Resources\Supplier\Orders\Pages;

use App\Filament\Supplier\Resources\Supplier\Orders\OrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected static bool $canCreateAnother = false;
}
