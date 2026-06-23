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

class ListFinancials extends ListRecords
{
    protected static string $resource = FinancialResource::class;

    public function getTableQuery(): ?Builder
    {
       $supplier_id = Auth::user()->supplier->user_id;

       if($this->activeTab === 'pending')
        {
            return Payable::where('vendor_id',$supplier_id)->where('paid_at',null);
        }

        return parent::getTableQuery();

    }

    public function getTabs(): array
    {
        $supplier_id = Auth::user()->supplier->user_id;

        return [
            'all' => Tab::make('All Payments')
                ->badge(fn () => Payment::where('payee_id', $supplier_id)->count()),

            'pending' => Tab::make('Pending')
                 ->badge(fn () => Payable::where('vendor_id', $supplier_id)->count())
                 ->badgeColor('warning'),

            'processing' => Tab::make('Processing')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'processing'))
                ->badge(fn () => Payment::where('payee_id', $supplier_id)
                    ->where('status', 'processing')
                    ->count())
                ->badgeColor('info'),

            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed'))
                ->badge(fn () => Payment::where('payee_id', $supplier_id)
                    ->where('status', 'completed')
                    ->count())
                ->badgeColor('success'),

            'failed' => Tab::make('Failed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'failed'))
                ->badge(fn () => Payment::where('payee_id', $supplier_id)
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
                    Select::make('year')
                        ->label('Year')
                        ->options(function(){
                            $start = 2024;
                            $end = now()->year;
                            return array_combine(range($end,$start), range($start,$end));
                        })
                        ->default(now()->year)
                        ->required(),

                    Select::make('month')
                        ->label('Month')
                        ->options([
                            ''  => 'All months (Full Year)',
                            1   => 'January',   2  => 'February',
                            3   => 'March',     4  => 'April',
                            5   => 'May',       6  => 'June',
                            7   => 'July',      8  => 'August',
                            9   => 'September', 10 => 'October',
                            11  => 'November',  12 => 'December',
                        ])
                        ->default('')
                        ->placeholder('All months (Full Year)'),
                ])->action(function(array $data){
                    $year = (int) $data['year'];
                    $month = ! empty($data['month']) ? (int) $data['month'] : null;

                    $url = route('supplier.financials.report', array_filter([
                        'year' => $year,
                        'month' => $month,
                    ]));

                    $this->redirect($url, navigate: false);
                })
                ->modalHeading('Download Statement')
                ->modalDescription('Select the period for your statement.')
                ->modalSubmitActionLabel('Download PDF')
                ,
        ];
    }
}
