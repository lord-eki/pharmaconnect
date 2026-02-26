<?php

namespace App\Filament\Operation\Resources\InsuranceClaims\Pages;

use App\Filament\Operation\Resources\InsuranceClaims\InsuranceClaimResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInsuranceClaims extends ListRecords
{
    protected static string $resource = InsuranceClaimResource::class;

   
}
