<?php

namespace App\Filament\Supplier\Resources\Supplier\OrderReports\Tables;

use App\Filament\Supplier\Resources\Supplier\Orders\OrderResource;
use App\Models\Order;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OrderReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order Number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'processing' => 'primary',
                        'shipped' => 'success',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),



                TextColumn::make('supplier_total')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable()
                    ->summarize(Sum::make()->money('KES')->label('Total Revenue')),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->badge()
                    ->color('success'),

                TextColumn::make('ordered_at')
                    ->label('Order Date')
                    ->dateTime('M d, Y')
                    ->sortable(),

                TextColumn::make('delivered_at')
                    ->label('Delivered Date')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->placeholder('Not delivered')
                    ->color(fn($state) => $state ? 'success' : 'gray'),

                TextColumn::make('expected_delivery')
                    ->label('Expected Delivery')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->color(
                        fn($state, $record) => $state && $state->isPast() && $record->status !== 'delivered'
                            ? 'danger'
                            : 'warning'
                    ),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                    ])
                    ->multiple(),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('ordered_from')
                            ->label('Ordered From')
                            ->placeholder('Select start date'),
                        DatePicker::make('ordered_until')
                            ->label('Ordered Until')
                            ->placeholder('Select end date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['ordered_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('ordered_at', '>=', $date),
                            )
                            ->when(
                                $data['ordered_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('ordered_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['ordered_from'] ?? null) {
                            $indicators[] = 'Ordered from ' . \Carbon\Carbon::parse($data['ordered_from'])->toFormattedDateString();
                        }
                        if ($data['ordered_until'] ?? null) {
                            $indicators[] = 'Ordered until ' . \Carbon\Carbon::parse($data['ordered_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                Filter::make('delivery_date_range')
                    ->form([
                        DatePicker::make('delivered_from')
                            ->label('Delivered From')
                            ->placeholder('Select start date'),
                        DatePicker::make('delivered_until')
                            ->label('Delivered Until')
                            ->placeholder('Select end date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['delivered_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('delivered_at', '>=', $date),
                            )
                            ->when(
                                $data['delivered_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('delivered_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['delivered_from'] ?? null) {
                            $indicators[] = 'Delivered from ' . \Carbon\Carbon::parse($data['delivered_from'])->toFormattedDateString();
                        }
                        if ($data['delivered_until'] ?? null) {
                            $indicators[] = 'Delivered until ' . \Carbon\Carbon::parse($data['delivered_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                Filter::make('overdue_orders')
                    ->label('Overdue Orders')
                    ->query(
                        fn(Builder $query): Builder => $query->where('expected_delivery', '<', now())
                            ->whereNotIn('status', ['delivered', 'cancelled'])
                    )
                    ->toggle(),
            ])->actions([
                Action::make('view')
                    ->url(fn(Order $record): string => OrderResource::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-o-eye')
                    ->color('info'),
            ])
            ->headerActions([
                Action::make('export_csv')
                    ->label('Export CSV')->outlined()
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->modalHeading('Export Orders (CSV)')
                    ->modalSubmitActionLabel('Export')
                    ->form(static::periodForm(allowAll: true))
                    ->action(fn(array $data, $livewire) => static::exportToCSV($livewire, $data)),

                Action::make('export_pdf')
                    ->label('Export PDF')->outlined()
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->modalHeading('Export Orders (PDF)')
                    ->modalSubmitActionLabel('Export')
                    ->form(static::periodForm(allowAll: false))
                    ->action(fn(array $data, $livewire) => static::exportToPDF($livewire, $data)),
            ])
            ->defaultSort('ordered_at', 'desc')
            ->poll('30s');
    }

    protected static function exportToCSV($livewire, array $data)
    {
        $supplier = Auth::user()->supplier;

        $query = static::applyPeriod($livewire->getFilteredTableQuery(), $data)
            ->setEagerLoads([])      // CSV doesn't need the heavy relations
            ->withCount('items');

        $filename = 'orders_report_' . $supplier->company_name . '_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Order Number', 'Status', 'Amount (KES)', 'Items Count', 'Ordered Date', 'Expected Delivery', 'Delivered Date']);

            foreach ($query->lazyById(500) as $order) {   // 500 rows in memory at a time
                fputcsv($file, [
                    $order->order_number,
                    $order->status,
                    number_format($order->supplier_total, 2),
                    $order->items_count,
                    $order->ordered_at?->format('Y-m-d H:i:s'),
                    $order->expected_delivery?->format('Y-m-d H:i:s'),
                    $order->delivered_at?->format('Y-m-d H:i:s') ?? 'Not delivered',
                ]);
            }

            fclose($file);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected static function exportToPDF($livewire, array $data)
    {
        $max      = 1000;
        $supplier = Auth::user()->supplier;

        $orders = static::applyPeriod($livewire->getFilteredTableQuery(), $data)
            ->orderByDesc('ordered_at')
            ->limit($max + 1)
            ->get();

        if ($orders->count() > $max) {
            Notification::make()
                ->title('Too many orders for a PDF')
                ->body("That period has more than {$max} orders. Narrow the dates or use the CSV export.")
                ->warning()
                ->send();

            return;
        }

        $html = view('pdf.orders-report', [
            'supplier'        => $supplier,
            'orders'          => $orders,
            'totalRevenue'    => $orders->sum('supplier_total'),
            'totalOrders'     => $orders->count(),
            'deliveredOrders' => $orders->where('status', 'delivered')->count(),
            'pendingOrders'   => $orders->whereIn('status', ['confirmed', 'processing', 'shipped'])->count(),
            'generatedAt'     => now(),
        ])->render();

        $pdf      = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $filename = 'orders_report_' . $supplier->company_name . '_' . now()->format('Y-m-d_His') . '.pdf';

        return response()->streamDownload(fn() => print($pdf->output()), $filename);
    }

    protected static function periodForm(bool $allowAll): array
    {
        $fields = [];

        if ($allowAll) {
            $fields[] = Toggle::make('all_time')
                ->label('Export all orders (no date limit)')
                ->helperText('Can take longer for large histories.')
                ->live();
        }

        $fields[] = DatePicker::make('from')
            ->label('From')
            ->default(now()->startOfMonth())
            ->maxDate(now())
            ->hidden(fn(Get $get) => $allowAll && $get('all_time'))
            ->required(fn(Get $get) => ! ($allowAll && $get('all_time')));

        $fields[] = DatePicker::make('until')
            ->label('Until')
            ->default(now())
            ->maxDate(now())
            ->afterOrEqual('from')
            ->hidden(fn(Get $get) => $allowAll && $get('all_time'))
            ->required(fn(Get $get) => ! ($allowAll && $get('all_time')));

        return $fields;
    }

    protected static function applyPeriod(Builder $query, array $data): Builder
    {
        if ($data['all_time'] ?? false) {
            return $query;
        }

        return $query->whereBetween('ordered_at', [
            Carbon::parse($data['from'])->startOfDay(),
            Carbon::parse($data['until'])->endOfDay(),
        ]);
    }
}
