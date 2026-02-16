<?php

namespace App\Filament\Operation\Resources\Orders\Pages;

use App\Filament\Operation\Resources\Orders\OrderResource;
use App\Services\OrderReassignmentService;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;

class ManageRejectedOrder extends Page
{
    use InteractsWithRecord;

    protected static string $resource = OrderResource::class;

    protected string $view = 'filament.operation.resources.orders.pages.manage-rejected-order';

    public array $reassignmentOptions = [];

    public function mount(int|string $record): void
    {

        $this->record = $this->resolveRecord($record);

        // Load reassignment options
        $reassignmentService = app(OrderReassignmentService::class);
        $this->reassignmentOptions = $reassignmentService->getReassignmentOptions($this->record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('auto_reassign')
                ->label('Auto-Reassign to Next Supplier')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->visible(fn () => $this->reassignmentOptions['can_auto_reassign'])
                ->requiresConfirmation()
                ->modalHeading('Auto-Reassign Order')
                ->modalDescription(function () {
                    $recommended = $this->reassignmentOptions['recommended'];
                    if (! $recommended) {
                        return 'No recommended supplier found.';
                    }

                    return "Reassign to {$recommended['supplier_name']} - Total: KES ".number_format($recommended['total_cost'], 2);
                })
                ->action(function () {
                    try {
                        $reassignmentService = app(OrderReassignmentService::class);

                        $success = $reassignmentService->autoReassignToNextSupplier($this->record);

                        if ($success) {
                            Notification::make()
                                ->success()
                                ->title('Order Reassigned')
                                ->body('Order has been automatically reassigned to the next available supplier.')
                                ->send();

                            // return redirect()->route('filament.operations.resources.orders.index');
                        } else {
                            throw new \Exception('Auto-reassignment failed. Please try manual reassignment.');
                        }

                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Reassignment Failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('manual_reassign')
                ->label('Manual Reassignment')
                ->icon('heroicon-o-user-circle')
                ->color('primary')
                ->form([
                    Section::make('Current Order Details')
                        ->schema([
                            ViewField::make('current_info')
                                ->view('filament.components.order-rejection-info', [
                                    'order' => $this->record,
                                    'options' => $this->reassignmentOptions,
                                ]),
                        ]),

                    Section::make('Select New Supplier')
                        ->schema([
                            Radio::make('new_supplier_id')
                                ->label('Available Suppliers')
                                ->required()
                                ->options(function () {
                                    $options = [];
                                    foreach ($this->reassignmentOptions['all_options'] as $option) {
                                        $label = "{$option['supplier_name']} - Total: KES ".
                                                number_format($option['total_cost'], 2);

                                        if (isset($this->reassignmentOptions['recommended']) &&
                                            $this->reassignmentOptions['recommended']['supplier_id'] === $option['supplier_id']) {
                                            $label .= ' ⭐ (Recommended - Cheapest)';
                                        }

                                        $options[$option['supplier_id']] = $label;
                                    }

                                    return $options;
                                })
                                ->descriptions(function () {
                                    $descriptions = [];
                                    foreach ($this->reassignmentOptions['all_options'] as $option) {
                                        $itemsList = [];
                                        foreach ($option['items'] as $medicineId => $itemData) {
                                            $itemsList[] = "Stock: {$itemData['stock_available']} units";
                                        }
                                        $descriptions[$option['supplier_id']] = implode(' | ', $itemsList);
                                    }

                                    return $descriptions;
                                }),

                            Textarea::make('reassignment_notes')
                                ->label('Notes (Optional)')
                                ->placeholder('Reason for choosing this supplier...')
                                ->rows(3),
                        ]),
                ])
                ->action(function (array $data) {
                    try {
                        $reassignmentService = app(OrderReassignmentService::class);

                        $reassignmentService->reassignToSupplier(
                            $this->record,
                            $data['new_supplier_id'],
                            $data['reassignment_notes'] ?? null
                        );

                        Notification::make()
                            ->success()
                            ->title('Order Reassigned')
                            ->body('Order has been successfully reassigned.')
                            ->send();

                        // return redirect()->route('filament.operations.resources.orders.index');

                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Reassignment Failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('cancel_order')
                ->label('Cancel Order')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel Order')
                ->modalDescription('This will permanently cancel the order. This action cannot be undone.')
                ->form([
                    Textarea::make('cancellation_reason')
                        ->label('Cancellation Reason')
                        ->required()
                        ->placeholder('Explain why this order is being cancelled...')
                        ->rows(4),
                ])
                ->action(function (array $data) {
                    try {
                        $this->record->cancel($data['cancellation_reason']);

                        Notification::make()
                            ->success()
                            ->title('Order Cancelled')
                            ->body('Order has been cancelled.')
                            ->send();

                        // return redirect()->route('filament.operations.resources.orders.index');

                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Cancellation Failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('view_history')
                ->label('View Rejection History')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->modalHeading('Rejection History')
                ->modalContent(view('filament.operation.modals.rejection-history', [
                    'history' => $this->record->reassignment_history ?? [],
                    'order' => $this->record,
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
        ];
    }
}
