<?php

namespace App\Filament\Supplier\Resources\Supplier\Inventories\Pages;

use App\Filament\Supplier\Resources\Supplier\Inventories\InventoryResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateInventory extends CreateRecord
{
    protected static string $resource = InventoryResource::class;

    protected static bool $canCreateAnother = false;

     protected function mutateFormDataBeforeCreate(array $data): array
    {
        $supplier = Auth::user()->supplier;

        $data['supplier_id'] = $supplier->id;
        $data['last_updated'] = now();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Product added to inventory successfully';
    }
}
