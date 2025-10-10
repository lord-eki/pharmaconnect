<?php

namespace App\Filament\Supplier\Resources\Supplier\Quotations\Pages;

use App\Filament\Supplier\Resources\Supplier\Quotations\QuotationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;


    protected static bool $canCreateAnother = false;
}
