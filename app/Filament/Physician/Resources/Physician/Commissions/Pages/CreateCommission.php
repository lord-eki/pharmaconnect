<?php

namespace App\Filament\Physician\Resources\Physician\Commissions\Pages;

use App\Filament\Physician\Resources\Physician\Commissions\CommissionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommission extends CreateRecord
{
    protected static string $resource = CommissionResource::class;

    protected static bool $canCreateAnother = false;
}
