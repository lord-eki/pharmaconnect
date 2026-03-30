<?php

namespace App\Filament\Physician\Resources\Physician\Prescriptions\Pages;

use App\Filament\Physician\Resources\Physician\Prescriptions\PrescriptionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditPrescription extends EditRecord
{
    protected static string $resource = PrescriptionResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components(CreatePrescription::getFormSchema());
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->icon('heroicon-o-trash'),
        ];
    }

    public function getRedirectUrl(): string|null
    {
        return $this->getResource()::getUrl('view',['record' => $this->getRecord()]);
    }
}
