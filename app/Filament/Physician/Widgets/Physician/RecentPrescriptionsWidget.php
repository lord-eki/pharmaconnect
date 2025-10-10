<?php

namespace App\Filament\Physician\Widgets\Physician;

use App\Models\Prescription;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RecentPrescriptionsWidget extends TableWidget
{

        protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
           ->query(
                Prescription::query()
                    ->where('physician_id', Auth::id())
                    ->latest('prescribed_at')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('prescription_number')
                    ->label('Prescription #')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Copied!')
                    ->weight('bold'),
                
                TextColumn::make('patient.full_name')
                    ->label('Patient')
                    ->searchable(['first_name', 'last_name'])
                    ->description(fn (Prescription $record): string => $record->patient->patient_number),
                
                BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'submitted',
                        'info' => 'processing',
                        'success' => 'fulfilled',
                        'danger' => 'cancelled',
                    ]),
                
                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->badge()
                    ->color('gray'),
                
                TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('KES')
                    ->weight('bold'),
                
                TextColumn::make('prescribed_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Action::make('view')
                    // ->url(fn (Prescription $record): string => 
                    //     route('filament.physician.resources.prescriptions.view', $record))
                    ->icon('heroicon-o-eye'),
            ])
            ->heading('Recent Prescriptions')
            ->description('Your 5 most recent prescriptions');
    }
}
