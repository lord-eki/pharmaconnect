<?php

namespace App\Filament\Physician\Resources\Physician\Prescriptions\Tables;

use App\Models\Prescription;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PrescriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('prescription_number')
                    ->label('Prescription #')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->searchable(['first_name', 'last_name']),

                TextColumn::make('patient.patient_number')
                    ->label('Patient #')
                    ->searchable(),

                TextColumn::make('items_count')
                    ->label('Medicines')
                    ->counts('items')
                    ->badge(),

                TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders')
                    ->badge()
                    ->color('info'),

                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('KES')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'submitted',
                        'info' => 'processing',
                        'success' => 'fulfilled',
                        'danger' => 'cancelled',
                    ]),

                IconColumn::make('insurance_covered')
                    ->boolean()
                    ->label('Insurance'),

                TextColumn::make('prescribed_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'processing' => 'Processing',
                        'fulfilled' => 'Fulfilled',
                        'cancelled' => 'Cancelled',
                    ]),

                TernaryFilter::make('insurance_covered')
                    ->label('Insurance Coverage'),

                Filter::make('prescribed_at')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('prescribed_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('prescribed_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),

                    Action::make('view_orders')
                        ->label('View Orders')
                        ->icon('heroicon-o-shopping-bag')
                        ->color('info')
                        ->modalHeading(fn (Prescription $record) => "Orders for {$record->prescription_number}")
                        ->modalWidth('5xl')
                        ->visible(fn (Prescription $record) => $record->orders()->count() > 0)
                        ->form(fn (Prescription $record) => static::getOrdersForm($record))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close'),

                    EditAction::make()
                        ->visible(fn (Prescription $record) => $record->status === 'draft'),

                    Action::make('submit')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Prescription $record) => $record->status === 'draft')
                        ->action(fn (Prescription $record) => $record->submit()),
                ])->label('More actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size(Size::Small)
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->can('delete_prescriptions')),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('physician_id', Auth::id()));
    }

    protected static function getOrdersForm(Prescription $prescription): array
    {
        $orders = $prescription->orders()->with(['supplier', 'items.medicine', 'delivery'])->get();

        return [
            Placeholder::make('summary')
                ->label('Summary')
                ->content($orders->count().' order(s) • Total: KES '.number_format($orders->sum('total_amount'), 2)),

            Section::make('Orders')
                ->schema(
                    $orders->map(function ($order) {
                        return Section::make($order->order_number)
                            // ->description('Supplier: ' . ($order->supplier->company_name ?? 'Awaiting Dispatch'))
                            ->icon('heroicon-o-shopping-bag')
                            ->schema([
                                Placeholder::make("status_{$order->id}")
                                    ->label('Status')
                                    ->content(fn () => new \Illuminate\Support\HtmlString(
                                        '<span class="fi-badge fi-badge-'.match ($order->status) {
                                            'pending' => 'warning',
                                            'confirmed' => 'info',
                                            'processing' => 'info',
                                            'shipped' => 'primary',
                                            'delivered' => 'success',
                                            'cancelled' => 'danger',
                                            default => 'gray',
                                        }.'">'.ucfirst($order->status).'</span>'
                                    )),

                                Placeholder::make("total_{$order->id}")
                                    ->label('Order Total')
                                    ->content('KES '.number_format($order->supplier_total ?? $order->total_amount, 2)),

                                Placeholder::make("ordered_{$order->id}")
                                    ->label('Ordered At')
                                    ->content($order->ordered_at?->format('M d, Y H:i') ?? 'N/A'),

                                Placeholder::make("expected_{$order->id}")
                                    ->label('Expected Delivery')
                                    ->content($order->expected_delivery?->format('M d, Y H:i') ?? 'N/A')
                                    ->visible(fn () => $order->expected_delivery),

                                Placeholder::make("delivered_{$order->id}")
                                    ->label('Delivered At')
                                    ->content($order->delivered_at?->format('M d, Y H:i'))
                                    ->visible(fn () => $order->delivered_at),

                                Section::make('Delivery Information')
                                    ->schema([
                                        Placeholder::make("delivery_number_{$order->id}")
                                            ->label('Delivery Number')
                                            ->content($order->delivery->delivery_number ?? 'N/A'),

                                        Placeholder::make("delivery_status_{$order->id}")
                                            ->label('Delivery Status')
                                            ->content(ucfirst($order->delivery->status ?? 'N/A')),

                                        Placeholder::make("delivery_fee_{$order->id}")
                                            ->label('Delivery Fee')
                                            ->content('KES '.number_format($order->delivery->delivery_fee ?? 0, 2)),
                                    ])
                                    ->columns(3)
                                    ->visible(fn () => $order->delivery)
                                    ->collapsible()
                                    ->collapsed(),

                                Section::make('Order Items')
                                    ->schema([
                                        Placeholder::make('items_table')
                                            ->label('')
                                            ->content(function () use ($order) {
                                                $medicineItems = $order->items->where('is_delivery_fee', false);

                                                $rows = $medicineItems->map(function ($item) {
                                                    $price = $item->supplier_price ?? $item->unit_price;
                                                    $total = $price * $item->quantity;

                                                    return '
                        <tr>
                            <td style="padding:10px 12px; border-bottom:1px solid #f1f5f9; vertical-align:top;">
                                <div style="font-weight:600; color:#1a1a2e;">
                                    '.e($item->medicine->generic_name).
                                                                    ($item->medicine->brand_name ? ' <span style="color:#64748b;font-weight:400;">('.e($item->medicine->brand_name).')</span>' : '').'
                                </div>
                                <div style="font-size:12px; color:#94a3b8; margin-top:2px;">
                                    '.e($item->medicine->strength).' &bull; '.e($item->medicine->dosage_form).'
                                </div>
                            </td>
                            <td style="padding:10px 12px; border-bottom:1px solid #f1f5f9; text-align:center; vertical-align:top;">
                                '.$item->quantity.' units
                            </td>
                            <td style="padding:10px 12px; border-bottom:1px solid #f1f5f9; text-align:right; vertical-align:top;">
                                KES '.number_format($price, 2).'
                            </td>
                            <td style="padding:10px 12px; border-bottom:1px solid #f1f5f9; text-align:right; vertical-align:top; font-weight:600; color:#1a1a2e;">
                                KES '.number_format($total, 2).'
                            </td>
                        </tr>';
                                                })->implode('');

                                                $grandTotal = $medicineItems->sum(function ($item) {
                                                    return ($item->supplier_price ?? $item->unit_price) * $item->quantity;
                                                });

                                                $html = '
                    <table style="width:100%; border-collapse:collapse; font-size:13px;">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th style="padding:8px 12px; text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:#64748b; border-bottom:2px solid #e2e8f0;">Medicine</th>
                                <th style="padding:8px 12px; text-align:center; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:#64748b; border-bottom:2px solid #e2e8f0;">Qty</th>
                                <th style="padding:8px 12px; text-align:right; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:#64748b; border-bottom:2px solid #e2e8f0;">Unit Price</th>
                                <th style="padding:8px 12px; text-align:right; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:#64748b; border-bottom:2px solid #e2e8f0;">Total</th>
                            </tr>
                        </thead>
                        <tbody>'.$rows.'</tbody>
                        <tfoot>
                            <tr style="background:#f8fafc;">
                                <td colspan="3" style="padding:10px 12px; text-align:right; font-weight:700; color:#374151; border-top:2px solid #e2e8f0;">Order Total</td>
                                <td style="padding:10px 12px; text-align:right; font-weight:700; color:#f97316; border-top:2px solid #e2e8f0;">KES '.number_format($grandTotal, 2).'</td>
                            </tr>
                        </tfoot>
                    </table>';

                                                return new \Illuminate\Support\HtmlString($html);
                                            }),
                                    ])
                                    ->columnSpanFull()
                                    ->collapsible()
                                    ->collapsed(true),
                            ])
                            ->columns(4)
                            ->collapsible()
                            ->collapsed(false);
                    })->toArray()
                ),
        ];
    }
}
