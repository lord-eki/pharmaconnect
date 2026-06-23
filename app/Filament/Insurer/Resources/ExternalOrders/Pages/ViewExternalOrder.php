<?php

namespace App\Filament\Insurer\Resources\ExternalOrders\Pages;

use App\Filament\Insurer\Resources\ExternalOrders\ExternalOrderResource;
use App\Models\ExternalOrder;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Log;

class ViewExternalOrder extends ViewRecord
{
    protected static string $resource = ExternalOrderResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([

                Grid::make(3)->columnSpanFull()->schema([

                    Section::make('Order Information')
                        ->columnSpan(2)
                        ->schema([
                            Grid::make(4)->schema([
                                TextEntry::make('order_number')
                                    ->label('Order Number')
                                    ->weight(FontWeight::Bold)
                                    ->copyable()
                                    ->columnSpan(1),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->colors([
                                        'secondary' => 'draft',
                                        'warning' => 'submitted',
                                        'info' => 'processing',
                                        'success' => 'fulfilled',
                                        'danger' => 'cancelled',
                                    ])
                                    ->columnSpan(1),

                                TextEntry::make('reference_number')
                                    ->label('Reference')
                                    ->placeholder('N/A')
                                    ->columnSpan(1),

                                TextEntry::make('total_amount')
                                    ->label('Order Total')
                                    ->money('KES')
                                    ->weight(FontWeight::Bold)
                                    ->color('success')
                                    ->columnSpan(1),
                            ]),
                        ]),

                    // Right 1/3 — Delivery status (only when exists)
                    Section::make('Delivery')
                        ->visible(fn ($record) => $record->delivery()->exists())
                        ->schema([
                            Grid::make(2)->schema([
                                TextEntry::make('delivery.delivery_number')
                                    ->label('Delivery Number')
                                    ->weight(FontWeight::Bold)
                                    ->copyable()
                                    ->placeholder('Not created yet'),

                                TextEntry::make('delivery.status')
                                    ->label('Status')
                                    ->badge()
                                    ->colors([
                                        'warning' => 'pending',
                                        'info' => ['assigned', 'in_transit'],
                                        'success' => 'delivered',
                                        'danger' => 'cancelled',
                                    ])
                                    ->placeholder('N/A'),
                            ]),

                        ]),
                ]),

                Section::make('Recipient & Delivery')
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        Grid::make(7)->schema([
                            TextEntry::make('recipient_name')
                                ->label('Recipient')
                                ->icon('heroicon-o-user')
                                ->columnSpan(2),

                            TextEntry::make('recipient_phone')
                                ->label('Phone')
                                ->icon('heroicon-o-phone')
                                ->copyable()
                                ->columnSpan(1),

                            TextEntry::make('recipient_email')
                                ->label('Email')
                                ->icon('heroicon-o-envelope')
                                ->placeholder('Not provided')
                                ->copyable()
                                ->columnSpan(2),

                            TextEntry::make('delivery_city')
                                ->label('City')
                                ->placeholder('Not specified')
                                ->columnSpan(1),

                            TextEntry::make('delivery_address')
                                ->label('Delivery Address')
                                ->icon('heroicon-o-map-pin')
                                ->columnSpan(3),

                            TextEntry::make('delivery_county')
                                ->label('County')
                                ->placeholder('Not specified')
                                ->columnSpan(2),
                        ]),
                    ]),

                Section::make('Order Items')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->contained(false)
                            ->schema([
                                Grid::make(5)->schema([
                                    TextEntry::make('medicine.generic_name')
                                        ->label('Medicine')
                                        ->weight(FontWeight::Bold)
                                        ->formatStateUsing(function ($record) {
                                            $medicine = $record->medicine;
                                            $brandInfo = $medicine->brand_name
                                                ? " ({$medicine->brand_name})"
                                                : '';

                                            return "{$medicine->generic_name}{$brandInfo}";
                                        })
                                        ->columnSpan(2),

                                    TextEntry::make('quantity')
                                        ->label('Qty')
                                        ->badge()
                                        ->color('info'),

                                    TextEntry::make('unit_price')
                                        ->label('Unit Price')
                                        ->money('KES'),

                                    TextEntry::make('total_price')
                                        ->label('Total')
                                        ->money('KES')
                                        ->weight(FontWeight::Bold),
                                ]),
                            ]),
                    ]),

                Section::make('Notes')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => ! empty($record->notes))
                    ->schema([
                        TextEntry::make('notes')
                            ->label('')
                            ->placeholder('No notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('Submit Order')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Submit Order')
                ->modalDescription('Are you sure you want to submit this order? This will create supplier orders and initiate the delivery process.')
                ->modalSubmitActionLabel('Yes, Submit')
                ->visible(fn (ExternalOrder $record) => $record->status === 'draft')
                ->action(function (ExternalOrder $record) {
                    try {
                        $record->submit();
                        Notification::make()->success()->icon('heroicon-o-check-circle')->title('Order Submitted')->body('Your order has been submitted successfully.')->send();
                    } catch (\Exception $e) {
                        Log::error($e->getMessage());
                        Notification::make()->danger()->icon('heroicon-o-exclamation-circle')->body('Unable to submit order')->send();
                    }
                }),

            EditAction::make()
                ->visible(fn (ExternalOrder $record) => $record->status === 'draft'),

            Action::make('cancel')
                ->label('Cancel Order')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel Order')
                ->modalDescription('Are you sure you want to cancel this order? This action cannot be undone.')
                ->form([
                    \Filament\Forms\Components\Textarea::make('cancel_reason')
                        ->label('Cancellation Reason')
                        ->required()
                        ->rows(3),
                ])
                ->visible(fn (ExternalOrder $record) => in_array($record->status, ['draft', 'submitted', 'processing']))
                ->action(function (ExternalOrder $record, array $data) {
                    try {
                        $record->cancel($data['cancel_reason']);
                        Notification::make()->success()->title('Order Cancelled')->body('The order has been cancelled successfully.')->send();
                    } catch (\Exception $e) {
                        Notification::make()->danger()->title('Cancellation Failed')->body($e->getMessage())->send();
                    }
                }),

            Action::make('back')
                ->label('Back to List')
                ->url($this->getResource()::getUrl('index'))
                ->outlined()
                ->color('gray'),
        ];
    }
}
