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
            ])->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('physician_id', Auth::id())
            );
    }
}
