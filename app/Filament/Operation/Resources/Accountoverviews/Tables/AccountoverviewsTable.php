<?php

namespace App\Filament\Operation\Resources\Accountoverviews\Tables;

use App\Models\Payable;
use App\Models\Receivable;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\AccountOverview;

class AccountoverviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
           ->query(AccountOverview::query())
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->copyable(),

                BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'success' => 'receivable',
                        'danger' => 'payable',
                    ])
                    ->icons([
                        'heroicon-o-arrow-down-tray' => 'receivable',
                        'heroicon-o-arrow-up-tray' => 'payable',
                    ]),

                TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'patient' => 'info',
                        'insurance' => 'primary',
                        'supplier' => 'warning',
                        'physician' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('party_name')
                    ->label('From/To')
                    ->searchable(),

                TextColumn::make('amount_in')
                    ->label('Money In')
                    ->money('KES')
                    ->color('success')
                    ->weight('bold'),

                TextColumn::make('amount_out')
                    ->label('Money Out')
                    ->money('KES')
                    ->color('danger')
                    ->weight('bold'),

               IconColumn::make('is_completed')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn ($record) => $record->is_completed ? 'Completed' : 'Pending'),

               TextColumn::make('related_document')
                    ->label('Related Document')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Transaction Type')
                    ->options([
                        'receivable' => 'Receivables (Money In)',
                        'payable' => 'Payables (Money Out)',
                    ]),

                SelectFilter::make('category')
                    ->label('Category')
                    ->options([
                        'patient' => 'Patient',
                        'insurance' => 'Insurance',
                        'supplier' => 'Supplier',
                        'physician' => 'Physician',
                    ]),

                Filter::make('completed')
                    ->query(fn (Builder $query): Builder => $query->where('is_completed', true))
                    ->label('Completed Only'),

                Filter::make('pending')
                    ->query(fn (Builder $query): Builder => $query->where('is_completed', false))
                    ->label('Pending Only'),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')
                            ->label('From Date'),
                        DatePicker::make('until')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
              
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]) ->defaultSort('date', 'desc')
            ->poll('30s');
    }


}
