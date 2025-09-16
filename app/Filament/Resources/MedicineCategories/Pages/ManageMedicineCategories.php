<?php

namespace App\Filament\Resources\MedicineCategories\Pages;

use App\Filament\Resources\MedicineCategories\MedicineCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMedicineCategories extends ManageRecords
{
    protected static string $resource = MedicineCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')->label('Add Medicine Category'),
        ];
    }
}
