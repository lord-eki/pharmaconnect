<?php

namespace App\Filament\Physician\Resources\Documents\Pages;

use App\Filament\Physician\Resources\Documents\DocumentResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn ($record) => ! $record->is_locked),

            Action::make('lock')
                ->label('Lock Document')
                ->icon('heroicon-o-lock-closed')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn ($record) => $record->lock())
                ->visible(fn ($record) => ! $record->is_locked),

            Action::make('unlock')
                ->label('Unlock Document')
                ->icon('heroicon-o-lock-open')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn ($record) => $record->unlock())
                ->visible(fn ($record) => $record->is_locked),

            Action::make('archive')
                ->label('Archive')
                ->icon('heroicon-o-archive-box')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn ($record) => $record->archive())
                ->visible(fn ($record) => $record->status === 'active'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
