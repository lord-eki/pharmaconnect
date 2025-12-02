<?php

namespace App\Filament\Insurer\Resources\Insurance\InsurerReports\Tables;

use App\Models\InsuranceClaim;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InsurerReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('submitted_at')
                    ->label('Period')
                    ->date()
                    ->sortable(),

                TextColumn::make('claim_number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->searchable(),

                TextColumn::make('policy_number')
                    ->searchable(),

                TextColumn::make('claimed_amount')
                    ->label('Claimed')
                    ->money('KES')
                    ->sortable()
                    ->summarize([
                        Sum::make()
                            ->money('KES')
                            ->label('Total Claimed'),
                    ]),

                TextColumn::make('approved_amount')
                    ->label('Approved')
                    ->money('KES')
                    ->sortable()
                    ->summarize([
                        Sum::make()
                            ->money('KES')
                            ->label('Total Approved'),
                    ]),

                TextColumn::make('deductible_amount')
                    ->label('Deductible')
                    ->money('KES')
                    ->sortable()
                    ->summarize([
                        Sum::make()
                            ->money('KES')
                            ->label('Total Deductible'),
                    ]),

                // TextColumn::make('net_amount')
                //     ->label('Net Payable')
                //     ->money('KES')
                //     ->getStateUsing(fn ($record) => $record->net_amount)
                //     ->summarize([
                //         Sum::make()
                //             ->money('KES')
                //             ->label('Total Payable'),
                //     ]),

                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'submitted',
                        'info' => 'under_review',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'primary' => 'paid',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'submitted' => 'Submitted',
                        'under_review' => 'Under Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'paid' => 'Paid',
                    ])
                    ->multiple(),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')
                            ->label('From Date'),
                        DatePicker::make('to')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('submitted_at', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('submitted_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'From '.\Carbon\Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if ($data['to'] ?? null) {
                            $indicators[] = 'To '.\Carbon\Carbon::parse($data['to'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                Filter::make('this_month')
                    ->query(fn (Builder $query) => $query->whereMonth('submitted_at', now()->month))
                    ->label('This Month'),

                Filter::make('last_month')
                    ->query(fn (Builder $query) => $query->whereMonth('submitted_at', now()->subMonth()->month))
                    ->label('Last Month'),

                Filter::make('this_quarter')
                    ->query(fn (Builder $query) => $query->whereBetween('submitted_at', [
                        now()->startOfQuarter(),
                        now()->endOfQuarter(),
                    ]))
                    ->label('This Quarter'),
            ])
            ->recordActions([
                Action::make('download_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('danger')
                    ->action(function ($record) {
                        return static::downloadClaimPdf($record);
                    }),

                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkAction::make('export_statement')
                    ->label('Export Statement')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function ($records) {
                        return static::exportStatement($records);
                    }),
            ])->headerActions([
                Action::make('monthly_statement')
                    ->label('Monthly Statement')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->form([
                        Select::make('month')
                            ->options([
                                1 => 'January', 2 => 'February', 3 => 'March',
                                4 => 'April', 5 => 'May', 6 => 'June',
                                7 => 'July', 8 => 'August', 9 => 'September',
                                10 => 'October', 11 => 'November', 12 => 'December',
                            ])
                            ->default(now()->month)
                            ->required(),
                        Select::make('year')
                            ->options(array_combine(
                                range(now()->year - 5, now()->year),
                                range(now()->year - 5, now()->year)
                            ))
                            ->default(now()->year)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        return static::generateMonthlyStatement($data['month'], $data['year']);
                    }),

                Action::make('analytics')
                    ->label('View Analytics')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->url(fn () => route('filament.Insurer.pages.claims-analytics')),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->groups([
                Group::make('submitted_at')
                    ->label('Submission Date')
                    ->date()
                    ->collapsible(),
                Group::make('status')
                    ->collapsible(),
            ]);
    }

    protected static function downloadClaimPdf(InsuranceClaim $claim)
    {
        $pdf = Pdf::loadView('pdf.insurance-claim', [
            'claim' => $claim->load(['prescription.items.medicine', 'prescription.orders', 'patient', 'insuranceProvider']),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, "claim-{$claim->claim_number}.pdf");
    }

    protected static function exportStatement($records)
    {
        $insuranceProvider = auth()->user()->insuranceProvider;

        $totalClaimed = $records->sum('claimed_amount');
        $totalApproved = $records->sum('approved_amount');
        $totalDeductible = $records->sum('deductible_amount');
        $totalPayable = $records->sum(fn ($r) => $r->net_amount);

        $pdf = Pdf::loadView('pdf.insurance-statement', [
            'provider' => $insuranceProvider,
            'claims' => $records,
            'summary' => [
                'total_claimed' => $totalClaimed,
                'total_approved' => $totalApproved,
                'total_deductible' => $totalDeductible,
                'total_payable' => $totalPayable,
                'total_claims' => $records->count(),
                'approved_claims' => $records->where('status', 'approved')->count(),
                'rejected_claims' => $records->where('status', 'rejected')->count(),
                'paid_claims' => $records->where('status', 'paid')->count(),
            ],
            'period' => [
                'from' => $records->min('submitted_at'),
                'to' => $records->max('submitted_at'),
            ],
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'insurance-statement-'.now()->format('Y-m-d').'.pdf');
    }

    protected static function generateMonthlyStatement(int $month, int $year)
    {
        $insuranceProvider = auth()->user()->insuranceProvider;

        // Build the query directly using the InsuranceClaim model
        $claims = InsuranceClaim::query()
            ->where('insurance_provider_id', $insuranceProvider->id ?? 0)
            ->with(['prescription.orders', 'patient'])
            ->whereMonth('submitted_at', $month)
            ->whereYear('submitted_at', $year)
            ->get();

        $totalClaimed = $claims->sum('claimed_amount');
        $totalApproved = $claims->sum('approved_amount');
        $totalDeductible = $claims->sum('deductible_amount');
        $totalPayable = $claims->sum(fn ($r) => $r->net_amount);

        $pdf = Pdf::loadView('pdf.insurance-monthly-statement', [
            'provider' => $insuranceProvider,
            'claims' => $claims,
            'month' => \Carbon\Carbon::create($year, $month)->format('F Y'),
            'summary' => [
                'total_claimed' => $totalClaimed,
                'total_approved' => $totalApproved,
                'total_deductible' => $totalDeductible,
                'total_payable' => $totalPayable,
                'total_claims' => $claims->count(),
                'approved_claims' => $claims->where('status', 'approved')->count(),
                'rejected_claims' => $claims->where('status', 'rejected')->count(),
                'paid_claims' => $claims->where('status', 'paid')->count(),
            ],
            'breakdown_by_status' => $claims->groupBy('status')->map->count(),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, "monthly-statement-{$year}-{$month}.pdf");
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
