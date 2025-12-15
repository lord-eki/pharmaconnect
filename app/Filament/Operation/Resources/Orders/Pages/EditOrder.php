<?php

namespace App\Filament\Operation\Resources\Orders\Pages;

use App\Filament\Operation\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
   protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            
            DeleteAction::make()
                ->visible(fn () => $this->record->status === 'pending_review'),

            Action::make('send_to_supplier')
                ->label('Send to Supplier')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn () => $this->record->status === 'pending_review')
                ->form([
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Notes for Supplier')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    try {
                        $this->record->sendToSupplier($data['notes'] ?? null);
                        
                        Notification::make()
                            ->title('Order sent to supplier')
                            ->success()
                            ->send();
                        
                        $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Order updated')
            ->body('The order has been updated successfully.');
    }
}
