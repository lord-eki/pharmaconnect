<?php

namespace App\Filament\Operation\Resources\Internals\Riders\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RidersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rider_code')
                    ->label('Rider Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('full_name')
                    ->label('Name')
                    ->getStateUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name', 'last_name'])
                    ->description(fn ($record) => $record->phone),

                TextColumn::make('phone')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-phone')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('license_number')
                    ->label('License')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('vehicle_type')
                    ->badge()
                    ->colors([
                        'info' => 'motorcycle',
                        'success' => 'car',
                        'warning' => 'bicycle',
                        'primary' => 'van',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable(),

                TextColumn::make('vehicle_registration')
                    ->label('Vehicle Reg.')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('base_location')
                    ->label('Base Location')
                    ->getStateUsing(fn ($record) => "{$record->base_city}, {$record->base_county}")
                    ->icon('heroicon-m-map-pin')
                    ->searchable(['base_city', 'base_county']),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_available')
                    ->label('Available')
                    ->boolean()
                    ->sortable()
                    ->action(
                        Action::make('toggle_availability')
                            ->requiresConfirmation()
                            ->action(fn ($record) => $record->update(['is_available' => ! $record->is_available]))
                    ),

                TextColumn::make('rating')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->icon('heroicon-m-star')
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'warning',
                        default => 'danger',
                    })
                    ->default('N/A')
                    ->alignCenter(),

                TextColumn::make('total_deliveries')
                    ->label('Deliveries')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('vehicle_type')
                    ->options([
                        'motorcycle' => 'Motorcycle',
                        'car' => 'Car',
                        'bicycle' => 'Bicycle',
                        'van' => 'Van',
                    ])
                    ->multiple(),

                SelectFilter::make('base_county')
                    ->searchable()
                    ->multiple()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All riders')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                TernaryFilter::make('is_available')
                    ->label('Availability')
                    ->placeholder('All riders')
                    ->trueLabel('Available only')
                    ->falseLabel('Unavailable only'),

                Filter::make('rating')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('rating_from')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->placeholder('Min rating'),
                                TextInput::make('rating_to')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->placeholder('Max rating'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['rating_from'],
                                fn (Builder $query, $rating): Builder => $query->where('rating', '>=', $rating),
                            )
                            ->when(
                                $data['rating_to'],
                                fn (Builder $query, $rating): Builder => $query->where('rating', '<=', $rating),
                            );
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('toggle_availability')
                        ->label(fn ($record) => $record->is_available ? 'Mark Unavailable' : 'Mark Available')
                        ->icon(fn ($record) => $record->is_available ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_available ? 'warning' : 'success')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->update(['is_available' => ! $record->is_available]))
                        ->visible(fn ($record) => $record->is_active),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ])->defaultSort('created_at', 'desc');
    }
}
