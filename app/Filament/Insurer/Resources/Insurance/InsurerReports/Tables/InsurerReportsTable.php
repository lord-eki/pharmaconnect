<?php

namespace App\Filament\Insurer\Resources\Insurance\InsurerReports\Tables;

use App\Models\InsuranceClaim;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

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

                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'submitted',
                        'info' => 'under_review',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'primary' => 'paid',
                    ]),
                TextColumn::make('pdf_path')->wrap()->label('PDF Generated')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
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
                Action::make('view_pdf')
                    ->label('View PDF')
                    ->icon('heroicon-o-eye')
                    ->color('info')->visible((fn ($record) => filled($record->pdf_path)))
                    ->action(function ($record) {
                        return static::viewClaimPdf($record);
                    }),

                Action::make('download_pdf')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('danger')->visible((fn ($record) => ! filled($record->pdf_path)))
                    ->action(function ($record) {
                        return static::downloadClaimPdf($record);
                    }),
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
                    ->color('primary')->outlined()
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
                    ->label('View Analytics')->outlined()
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

    /**
     * Generate and save PDF to storage, then return the path
     */
    protected static function generateAndSaveClaimPdf(InsuranceClaim $claim, bool $forceRegenerate = false): string
    {
        // Check if PDF exists and is recent (within last hour) unless force regenerate
        if (! $forceRegenerate && $claim->pdf_path && Storage::disk('public')->exists($claim->pdf_path)) {
            // Check if PDF is recent (within last hour)
            if ($claim->pdf_generated_at && $claim->pdf_generated_at->diffInMinutes(now()) < 60) {
                \Log::info('Using cached PDF', [
                    'claim_id' => $claim->id,
                    'pdf_path' => $claim->pdf_path,
                ]);

                return $claim->pdf_path;
            }
        }

        // Load all necessary relationships
        $claim->load([
            'prescription.items.medicine',
            'prescription.orders.items.medicine',
            'prescription.orders.supplier',
            'prescription.physician',
            'patient',
            'insuranceProvider',
        ]);

        // Get the insurance provider
        $insuranceProvider = $claim->insuranceProvider;

        // Get branding data
        if (method_exists($insuranceProvider, 'getBrandingData')) {
            $branding = $insuranceProvider->getBrandingData();
        } else {
            $branding = [
                'logo_url' => $insuranceProvider->logo_path ? Storage::disk('public')->url($insuranceProvider->logo_path) : null,
                'header_text' => $insuranceProvider->header_text
                    ?: $insuranceProvider->form_header
                    ?: "Insurance Claim Form - {$insuranceProvider->company_name}",
                'footer_text' => $insuranceProvider->footer_text
                    ?: $insuranceProvider->form_footer
                    ?: "Contact: {$insuranceProvider->phone} | Email: {$insuranceProvider->email}",
                'primary_color' => $insuranceProvider->primary_color ?? '#000000',
                'secondary_color' => $insuranceProvider->secondary_color ?? '#666666',
            ];
        }

        // Generate PDF
        $pdf = Pdf::loadView('pdf.insurance-claim', [
            'claim' => $claim,
            'branding' => $branding,
        ])
            ->setPaper('a4')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        // Define storage path - organized by provider and date
        $date = now();
        $directory = "insurance-claims/{$insuranceProvider->id}/{$date->format('Y')}/{$date->format('m')}";
        $filename = "claim-{$claim->claim_number}.pdf";
        $path = "{$directory}/{$filename}";

        // Delete old PDF if exists
        if ($claim->pdf_path && Storage::disk('public')->exists($claim->pdf_path)) {
            Storage::disk('public')->delete($claim->pdf_path);
        }

        // Ensure directory exists
        Storage::disk('public')->makeDirectory($directory);

        // Save to storage
        Storage::disk('public')->put($path, $pdf->output());

        // Update claim record with PDF path
        $claim->update([
            'pdf_path' => $path,
            'pdf_generated_at' => now(),
        ]);

        \Log::info('Insurance claim PDF generated and saved', [
            'claim_id' => $claim->id,
            'claim_number' => $claim->claim_number,
            'path' => $path,
            'size' => Storage::disk('public')->size($path),
        ]);

        return $path;
    }

    /**
     * View PDF inline in browser
     */
    protected static function viewClaimPdf(InsuranceClaim $claim)
    {
        try {
            // Generate or get cached PDF
            $path = static::generateAndSaveClaimPdf($claim);

            // Check if file exists
            if (! Storage::disk('public')->exists($path)) {
                Notification::make()
                    ->title('PDF Not Found')
                    ->body('The PDF file could not be found.')
                    ->danger()
                    ->send();

                return;
            }

            return redirect()->to(Storage::disk('public')->url($path));

        } catch (\Exception $e) {
            \Log::error('Failed to view claim PDF', [
                'claim_id' => $claim->id,
                'claim_number' => $claim->claim_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Error Viewing PDF')
                ->body('Unable to display the PDF: '.$e->getMessage())
                ->danger()
                ->send();

            return redirect()->back();
        }
    }

    /**
     * Download PDF to user's device
     */
    protected static function downloadClaimPdf(InsuranceClaim $claim)
    {
        try {
            // Generate and save PDF (uses cache if recent)
            $path = static::generateAndSaveClaimPdf($claim);

            // Check if file exists
            if (! Storage::disk('public')->exists($path)) {
                throw new \Exception('PDF file not found after generation');
            }

            // Notify user
            Notification::make()
                ->title('Download Ready')
                ->body('Claim PDF is now downloading.')
                ->success()
                ->send();

            // Download the file
            return Storage::disk('public')->download(
                $path,
                "claim-{$claim->claim_number}.pdf",
                [
                    'Content-Type' => 'application/pdf',
                ]
            );

        } catch (\Exception $e) {
            \Log::error('Failed to download claim PDF', [
                'claim_id' => $claim->id,
                'claim_number' => $claim->claim_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Download Failed')
                ->body('Unable to generate the PDF: '.$e->getMessage())
                ->danger()
                ->send();

            return redirect()->back();
        }
    }

    /**
     * Force regenerate PDF incase of claim data changes
     */
    protected static function regenerateClaimPdf(InsuranceClaim $claim)
    {
        try {
            $path = static::generateAndSaveClaimPdf($claim, true);

            Notification::make()
                ->title('PDF Regenerated')
                ->body('The claim PDF has been regenerated with the latest data.')
                ->success()
                ->send();

            return $path;

        } catch (\Exception $e) {
            \Log::error('Failed to regenerate claim PDF', [
                'claim_id' => $claim->id,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Regeneration Failed')
                ->body('Unable to regenerate the PDF.')
                ->danger()
                ->send();

            return null;
        }
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
