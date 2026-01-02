<?php

namespace App\Filament\Operation\Resources\Orders\Pages;

use App\Filament\Operation\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
   protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => !in_array($this->record->status, ['delivered', 'cancelled'])),

            Action::make('send_to_supplier')
                ->label('Send to Supplier')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn () => $this->record->status === 'pending_review')
                ->form([
                    Textarea::make('notes')
                        ->label('Notes for Supplier')
                        ->helperText('Optional notes that will be added to the order')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    try {
                        $this->record->sendToSupplier($data['notes'] ?? null);
                        
                        Notification::make()
                            ->title('Order sent to supplier')
                            ->body("Order {$this->record->order_number} has been sent to {$this->record->supplier->name}")
                            ->success()
                            ->send();
                        
                        $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error sending order')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Send Order to Supplier')
                ->modalDescription(fn () => "This will notify {$this->record->supplier->name} and make the order visible to them. They will be able to confirm and process the order.")
                ->modalSubmitActionLabel('Send to Supplier'),

            Action::make('cancel')
                ->label('Cancel Order')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => in_array($this->record->status, ['pending_review', 'sent_to_supplier', 'confirmed']))
                ->form([
                    Textarea::make('reason')
                        ->label('Cancellation Reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    try {
                        $this->record->cancel($data['reason']);
                        
                        Notification::make()
                            ->title('Order cancelled')
                            ->body('The order has been cancelled successfully')
                            ->success()
                            ->send();
                        
                        $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error cancelling order')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Cancel Order')
                ->modalDescription('Are you sure you want to cancel this order? This action cannot be undone.')
                ->modalSubmitActionLabel('Cancel Order'),

            Action::make('view_prescription')
                ->label('View Prescription')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                // ->url(fn () => route('filament.operations.resources.prescriptions.view', ['record' => $this->record->prescription_id]))
                ->visible(fn () => $this->record->prescription_id),

            Action::make('view_delivery')
                ->label('View Delivery')
                ->icon('heroicon-o-truck')
                ->color('gray')
                // ->url(fn () => $this->record->delivery ? route('filament.operations.resources.deliveries.view', ['record' => $this->record->delivery->id]) : null)
                ->visible(fn () => $this->record->delivery),
        ];
    }
}
