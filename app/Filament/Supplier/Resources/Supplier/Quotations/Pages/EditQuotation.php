<?php

namespace App\Filament\Supplier\Resources\Supplier\Quotations\Pages;

use App\Filament\Supplier\Resources\Supplier\Quotations\QuotationResource;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

   protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            
            Action::make('respond')
                ->label('Submit Response')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn ($record) => $record->status === 'pending' && !$record->valid_until->isPast())
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->update(['status' => 'sent']);
                })
                ->successNotificationTitle('Quotation response submitted successfully')
                // ->after(fn () => redirect()->route(route: 'filament.supplier.resources.quotations.index')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Quotation updated successfully';
    }

    protected function beforeSave(): void
    {
        // Recalculate total amount based on quotation items
        $totalAmount = $this->record->quotationItems()->sum('total_price');
        $this->record->total_amount = $totalAmount;
    }
}
