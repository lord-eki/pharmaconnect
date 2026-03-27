<?php

namespace App\Filament\Operation\Resources\Orders\Pages;

use App\Exceptions\StockShortageException;
use App\Filament\Operation\Resources\Orders\OrderResource;
use App\Models\Supplier;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\View;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [

            // ── Send to Supplier ───────────────────────────────────────────
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
                            ->body("Order {$this->record->order_number} has been sent to {$this->record->supplier->company_name}")
                            ->success()
                            ->send();

                        $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (StockShortageException $e) {
                     
                        $shortages    = $e->getShortages();
                        $medicineList = collect($shortages)
                            ->map(fn ($s) => "• {$s['medicine_name']}: need {$s['required_quantity']}, only {$s['available_stock']} in stock")
                            ->join("\n");

                        Notification::make()
                            ->title('Stock shortage — cannot send')
                            ->body("The following medicines are out of stock at the current supplier:\n\n{$medicineList}\n\nUse the \"Reassign Supplier\" button to switch to a supplier who has stock.")
                            ->warning()
                            ->persistent()
                            ->send();

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
                ->modalDescription(fn () => "This will notify {$this->record->supplier?->company_name} and make the order visible to them. They will be able to confirm and process the order.")
                ->modalSubmitActionLabel('Send to Supplier'),

          
            Action::make('reassign_supplier')
                ->label('Reassign Supplier')
                ->icon('heroicon-o-arrows-right-left')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'pending_review')
                ->form(function (): array {
                    $shortages = $this->record->checkStockShortages();

                    $fields = [];

                    if (! empty($shortages)) {
                        $shortageLines = collect($shortages)
                            ->map(fn ($s) => "**{$s['medicine_name']}**: need {$s['required_quantity']}, available {$s['available_stock']}")
                            ->join('  •  ');

                        $fields[] = Placeholder::make('shortage_notice')
                            ->label('⚠ Stock Shortages Detected')
                            ->content("The medicines below are out of stock at **{$this->record->supplier->company_name}**:\n\n{$shortageLines}\n\nSelect a new supplier who has sufficient stock for all items.");
                    }

                    $orderItems = $this->record->items()
                        ->where('is_delivery_fee', false)
                        ->get(['medicine_id', 'quantity']);

                    // Start with all verified, active suppliers excluding the current one
                    $candidateSupplierIds = DB::table('supplier_medicines as sm')
                        ->join('suppliers as s', 's.id', '=', 'sm.supplier_id')
                        ->where('sm.is_available', true)
                        ->where('sm.stock_quantity', '>', 0)
                        ->where('sm.supplier_id', '!=', $this->record->supplier_id)
                        ->where('s.is_active', true)
                        ->pluck('sm.supplier_id')
                        ->unique();

                    // Filter down to suppliers who have all medicines with enough stock
                    $fullyCapableSupplierIds = $candidateSupplierIds->filter(function ($supplierId) use ($orderItems) {
                        foreach ($orderItems as $item) {
                            $sm = DB::table('supplier_medicines')
                                ->where('medicine_id', $item->medicine_id)
                                ->where('supplier_id', $supplierId)
                                ->where('is_available', true)
                                ->where('stock_quantity', '>=', $item->quantity)
                                ->first();

                            if (! $sm) {
                                return false;
                            }
                        }
                        return true;
                    });

                    $supplierOptions = [];
                    foreach ($fullyCapableSupplierIds as $supplierId) {
                        $supplier = Supplier::find($supplierId);
                        if (! $supplier) continue;

                        $subtotal = 0;
                        foreach ($orderItems as $item) {
                            $sm = DB::table('supplier_medicines')
                                ->where('medicine_id', $item->medicine_id)
                                ->where('supplier_id', $supplierId)
                                ->first();
                            $subtotal += ($sm->unit_price ?? 0) * $item->quantity;
                        }

                        $supplierOptions[$supplierId] = "{$supplier->company_name} — supplier cost: KES " . number_format($subtotal, 2);
                    }

                    if (empty($supplierOptions)) {
                        $fields[] = Placeholder::make('no_suppliers')
                            ->label('No Alternative Suppliers Available')
                            ->content('There are no other verified, active suppliers who can fulfil all items in this order with sufficient stock. You may need to wait for stock to be replenished or cancel the order.');

                        $fields[] = Select::make('new_supplier_id')
                            ->label('New Supplier')
                            ->options([])
                            ->placeholder('No eligible suppliers found')
                            ->disabled()
                            ->required();
                    } else {
                        $fields[] = Select::make('new_supplier_id')
                            ->label('New Supplier')
                            ->helperText('Only suppliers with stock for all items are shown, sorted cheapest first.')
                            ->options($supplierOptions)
                            ->searchable()
                            ->required();
                    }

                    $fields[] = Textarea::make('reason')
                        ->label('Reason for Reassignment')->required()
                        ->placeholder('e.g. Current supplier is out of stock')
                        ->rows(2);

                    return $fields;
                })
                ->action(function (array $data): void {
                    $newSupplierId = (int) $data['new_supplier_id'];
                    $reason        = $data['reason'] ?? null;

                    if (! $newSupplierId) {
                        Notification::make()
                            ->title('No supplier selected')
                            ->body('Please select a supplier to reassign the order to.')
                            ->warning()
                            ->send();
                        return;
                    }

                    try {
                        $this->record->reassignToSupplier($newSupplierId, $reason);

                        $newSupplier = Supplier::find($newSupplierId);

                        Notification::make()
                            ->title('Order reassigned')
                            ->body("Order {$this->record->order_number} has been reassigned to {$newSupplier?->company_name}. Review it and click \"Send to Supplier\" when ready.")
                            ->success()
                            ->send();

                        $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error reassigning order')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalHeading('Reassign Order to Another Supplier')
                ->modalSubmitActionLabel('Reassign Order'),

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

          
        ];
    }
}