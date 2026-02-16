<?php

namespace App\Filament\Operation\Widgets;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class RejectedOrdersWidget extends TableWidget
{

    protected static ?int $sort = 2;
    protected static ?string $heading = 'Orders Pending Reassignment';
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->pendingReassignment()
                    ->with(['supplier', 'prescription', 'externalOrder'])
                    ->latest('rejected_at')
            )
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('supplier.company_name')
                    ->label('Rejected Supplier')
                    ->searchable()
                    ->color('danger'),

                TextColumn::make('rejection_reason')
                    ->label('Reason')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->rejection_reason),

                TextColumn::make('reassignment_count')
                    ->label('Rejections')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 3 => 'danger',
                        $state >= 2 => 'warning',
                        default => 'info',
                    }),

                TextColumn::make('total_amount')
                    ->label('Order Total')
                    ->money('KES')
                    ->sortable(),

                TextColumn::make('rejected_at')
                    ->label('Rejected')
                    ->dateTime()
                    ->sortable()
                    ->since(),

                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending_reassignment',
                        'danger' => 'needs_manual_assignment',
                    ]),
            ])
            ->actions([
                Action::make('auto_reassign')
                    ->label('Auto-Reassign')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->action(function (Order $record) {
                        $reassignmentService = app(\App\Services\OrderReassignmentService::class);
                        
                        try {
                            $success = $reassignmentService->autoReassignToNextSupplier($record);
                            
                            if ($success) {
                                \Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title('Order Reassigned')
                                    ->body("Order {$record->order_number} reassigned successfully.")
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title('Manual Action Required')
                                    ->body("Could not auto-reassign order {$record->order_number}.")
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),

                Action::make('manage')
                    ->label('Manage')
                    ->icon('heroicon-o-cog')
                    ->url(fn (Order $record) => route('filament.Operation.resources.orders.manage-rejected', $record)),
            ])
            ->emptyStateHeading('No orders pending reassignment')
            ->emptyStateDescription('All orders are currently assigned to suppliers.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
