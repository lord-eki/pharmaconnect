<?php

namespace App\Filament\Supplier\Resources\Supplier\OrderReports\Tables;

use App\Filament\Supplier\Resources\Supplier\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
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
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'processing' => 'primary',
                        'shipped' => 'success',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('quotation.prescription.prescription_number')
                    ->label('Prescription')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('quotation.prescription.physician.name')
                    ->label('Physician')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quotation.prescription.patient.first_name')
                    ->label('Patient')
                    ->formatStateUsing(fn ($record) => $record->quotation->prescription->patient
                        ? "{$record->quotation->prescription->patient->first_name} {$record->quotation->prescription->patient->last_name}"
                        : 'N/A'
                    )
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

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
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                TextColumn::make('expected_delivery')
                    ->label('Expected Delivery')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->color(fn ($state, $record) => $state && $state->isPast() && $record->status !== 'delivered'
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
                    ->multiple()
                    ->default(['confirmed', 'processing', 'shipped', 'delivered']),

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
                                fn (Builder $query, $date): Builder => $query->whereDate('ordered_at', '>=', $date),
                            )
                            ->when(
                                $data['ordered_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('ordered_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['ordered_from'] ?? null) {
                            $indicators[] = 'Ordered from '.\Carbon\Carbon::parse($data['ordered_from'])->toFormattedDateString();
                        }
                        if ($data['ordered_until'] ?? null) {
                            $indicators[] = 'Ordered until '.\Carbon\Carbon::parse($data['ordered_until'])->toFormattedDateString();
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
                                fn (Builder $query, $date): Builder => $query->whereDate('delivered_at', '>=', $date),
                            )
                            ->when(
                                $data['delivered_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('delivered_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['delivered_from'] ?? null) {
                            $indicators[] = 'Delivered from '.\Carbon\Carbon::parse($data['delivered_from'])->toFormattedDateString();
                        }
                        if ($data['delivered_until'] ?? null) {
                            $indicators[] = 'Delivered until '.\Carbon\Carbon::parse($data['delivered_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                Filter::make('overdue_orders')
                    ->label('Overdue Orders')
                    ->query(fn (Builder $query): Builder => $query->where('expected_delivery', '<', now())
                        ->whereNotIn('status', ['delivered', 'cancelled'])
                    )
                    ->toggle(),

                SelectFilter::make('physician')
                    ->relationship('quotation.prescription.physician', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
            ])->actions([
                Action::make('view')
                    ->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-o-eye')
                    ->color('info'),
            ])
            ->headerActions([
                Action::make('export_csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function ($livewire) {
                        return static::exportToCSV($livewire);
                    }),

                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->action(function ($livewire) {
                        return static::exportToPDF($livewire);
                    }),
            ])
            ->defaultSort('ordered_at', 'desc')
            ->poll('30s');

    }

    protected static function exportToCSV($livewire)
    {
        $supplier = Auth::user()->supplier;

        // Apply table filters
        $query = $livewire->getFilteredTableQuery();

        $orders = $query->get();

        $filename = 'orders_report_'.$supplier->company_name.'_'.now()->format('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, [
                'Order Number',
                'Status',
                'Prescription Number',
                'Physician',
                'Patient',
                'Amount (KES)',
                'Items Count',
                'Ordered Date',
                'Expected Delivery',
                'Delivered Date',
            ]);

            // CSV Data
            foreach ($orders as $order) {
                $prescription = $order->quotation->prescription;
                $patient = $prescription->patient;

                fputcsv($file, [
                    $order->order_number,
                    $order->status,
                    $prescription->prescription_number,
                    $prescription->physician->name ?? 'N/A',
                    $patient ? "{$patient->first_name} {$patient->last_name}" : 'N/A',
                    number_format($order->supplier_total, 2),
                    $order->items->count(),
                    $order->ordered_at?->format('Y-m-d H:i:s'),
                    $order->expected_delivery?->format('Y-m-d H:i:s'),
                    $order->delivered_at?->format('Y-m-d H:i:s') ?? 'Not delivered',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected static function exportToPDF($livewire)
    {
        $supplier = Auth::user()->supplier;

        // Apply table filters
        $query = $livewire->getFilteredTableQuery();

        $orders = $query->get();

        // Calculate summary statistics
        $totalRevenue = $orders->sum('supplier_total');
        $totalOrders = $orders->count();
        $deliveredOrders = $orders->where('status', 'delivered')->count();
        $pendingOrders = $orders->whereIn('status', ['confirmed', 'processing', 'shipped'])->count();

        $html = view('pdf.orders-report', [
            'supplier' => $supplier,
            'orders' => $orders,
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'deliveredOrders' => $deliveredOrders,
            'pendingOrders' => $pendingOrders,
            'generatedAt' => now(),
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);

        $filename = 'orders_report_'.$supplier->company_name.'_'.now()->format('Y-m-d_His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }
}
