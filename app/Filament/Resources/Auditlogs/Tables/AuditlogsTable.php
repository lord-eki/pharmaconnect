<?php

namespace App\Filament\Resources\Auditlogs\Tables;

use App\Models\AuditLog;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;


class AuditlogsTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')

            ->columns([
           
                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('M d, Y H:i:s')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->placeholder('System')
                    ->icon('heroicon-m-user-circle'),

                TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                TextColumn::make('auditable_type')
                    ->label('Model')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->badge()
                    ->color('gray')
                    ->searchable(),


                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->icon('heroicon-m-globe-alt')
                    ->copyable()
                    ->copyMessage('IP copied')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('url')
                    ->label('URL')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->options([
                        'created'  => 'Created',
                        'updated'  => 'Updated',
                        'deleted'  => 'Deleted',
                        'restored' => 'Restored',
                    ])
                    ->label('Event Type'),

                SelectFilter::make('auditable_type')
                    ->label('Model Type')
                    ->options(
                        AuditLog::query()
                            ->distinct()
                            ->pluck('auditable_type', 'auditable_type')
                            ->mapWithKeys(fn ($type) => [$type => class_basename($type)])
                            ->toArray()
                    ),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'],  fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'])  $indicators[] = 'From: '  . $data['from'];
                        if ($data['until']) $indicators[] = 'Until: ' . $data['until'];
                        return $indicators;
                    }),
            ])
            ->recordActions([
                 ActionGroup::make([
                    Action::make('view_details')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('primary')
                        ->modalHeading(fn (AuditLog $record) => ucfirst($record->event) . ' — ' . class_basename($record->auditable_type) . ' #' . $record->auditable_id)
                        ->modalContent(fn (AuditLog $record) => view(
                            'filament.audit-log-detail',
                            ['record' => $record]
                        ))
                        ->modalWidth('4xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])->striped();
    }
}
