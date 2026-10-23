<?php

namespace App\Filament\Supplier\Resources\Supplier\Financials\Pages;

use App\Filament\Supplier\Resources\Supplier\Financials\FinancialResource;
use App\Filament\Supplier\Resources\Supplier\Financials\Widgets\Supplier\FinancialStatsWidget;
use App\Models\Payment;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Select;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use App\Models\Payable;
use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Utilities\Get;

class ListFinancials extends ListRecords
{
    protected static string $resource = FinancialResource::class;

    public function getTableQuery(): ?Builder
    {
        $supplier_id = Auth::user()->supplier->user_id;

        if ($this->activeTab === 'pending') {
            return Payable::where('vendor_id', $supplier_id)->where('paid_at', null);
        }

        return parent::getTableQuery();
    }

    public function getTabs(): array
    {
        $supplier_id = Auth::user()->supplier->user_id;

        return [
            'all' => Tab::make('All Payments')
                ->badge(fn() => Payment::where('payee_id', $supplier_id)->count()),

            'pending' => Tab::make('Pending')
                ->badge(fn() => Payable::where('vendor_id', $supplier_id)->count())
                ->badgeColor('warning'),

            'processing' => Tab::make('Processing')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'processing'))
                ->badge(fn() => Payment::where('payee_id', $supplier_id)
                    ->where('status', 'processing')
                    ->count())
                ->badgeColor('info'),

            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'completed'))
                ->badge(fn() => Payment::where('payee_id', $supplier_id)
                    ->where('status', 'completed')
                    ->count())
                ->badgeColor('success'),

            'failed' => Tab::make('Failed')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'failed'))
                ->badge(fn() => Payment::where('payee_id', $supplier_id)
                    ->where('status', 'failed')
                    ->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinancialStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Download Statement')
                ->icon('heroicon-o-arrow-down-tray')->outlined()
                ->color('info')->form([
                    DatePicker::make('from')
                        ->label('From')
                        ->default(now()->startOfMonth())
                        ->maxDate(now())
                        ->required()
                        ->live(),
                    DatePicker::make('until')
                        ->label('Until')
                        ->default(now())
                        ->maxDate(now())
                        ->afterOrEqual('from')
                        ->rule(fn(Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                            if ($get('from') && Carbon::parse($get('from'))->diffInDays(Carbon::parse($value)) > 92) {
                                $fail('Please choose a period of 3 months or less');
                            }
                        })
                ])->action(function (array $data) {
                    $this->redirect(route('supplier.financials.report', [
                        'from'  => $data['from'],
                        'until' => $data['until'],
                    ]), navigate: false);
                })
                ->modalHeading('Download Statement')
                ->modalDescription('Select the period for your statement.')
                ->modalSubmitActionLabel('Download PDF'),
        ];
    }
}
