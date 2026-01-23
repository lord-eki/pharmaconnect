<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewDocument extends ViewRecord
{
  protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            
            Action::make('download')
                ->label('Download Document')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $record = $this->getRecord();
                    if (\Illuminate\Support\Facades\Storage::exists($record->file_path)) {
                        $record->logAccess(auth()->user(), 'download');
                        return \Illuminate\Support\Facades\Storage::download($record->file_path, $record->file_name);
                    }
                    
                    \Filament\Notifications\Notification::make()
                        ->title('File not found')
                        ->danger()
                        ->send();
                }),

            Action::make('verify')
                ->label('Verify Document')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->getRecord()->verification_status === 'pending')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\Textarea::make('verification_notes')
                        ->label('Verification Notes (Optional)')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->getRecord()->verify(auth()->user(), $data['verification_notes'] ?? null);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Document verified')
                        ->success()
                        ->send();
                }),

            DeleteAction::make()
                ->visible(fn () => !$this->getRecord()->is_locked),

            ForceDeleteAction::make(),
            
            RestoreAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document Information')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('document_number')
                                ->label('Document Number')
                                ->weight(weight: FontWeight::Bold)
                                ->copyable(),
                            TextEntry::make('category.name')
                                ->label('Category')
                                ->badge(),
                            TextEntry::make('document_type')
                                ->label('Type')
                                ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucwords($state, '_')))
                                ->badge(),
                        ]),
                        TextEntry::make('title')
                            ->label('Title')
                            ->columnSpanFull(),
                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                    ]),

                Section::make('File Information')
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('file_name')
                                ->label('File Name'),
                            TextEntry::make('mime_type')
                                ->label('File Type')
                                ->badge(),
                            TextEntry::make('file_size')
                                ->label('File Size')
                                ->formatStateUsing(fn ($state) => number_format($state / 1024, 2) . ' KB'),
                            TextEntry::make('file_hash')
                                ->label('File Hash')
                                ->copyable()
                                ->limit(20),
                        ]),
                    ]),

                Section::make('Verification Details')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('verification_status')
                                ->label('Status')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'pending' => 'warning',
                                    'verified' => 'success',
                                    'rejected' => 'danger',
                                }),
                            TextEntry::make('verifiedBy.name')
                                ->label('Verified By'),
                            TextEntry::make('verified_at')
                                ->label('Verified At')
                                ->dateTime(),
                        ]),
                        TextEntry::make('verification_notes')
                            ->label('Verification Notes')
                            ->columnSpanFull(),
                    ]),

                Section::make('Status & Control')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('status')
                                ->badge(),
                            IconEntry::make('is_locked')
                                ->label('Locked')
                                ->boolean(),
                            TextEntry::make('version')
                                ->label('Version')
                                ->formatStateUsing(fn ($state) => "v{$state}"),
                        ]),
                    ]),
            ]);
    }

    protected function afterView(): void
    {
        $this->getRecord()->logAccess(auth()->user(), 'viewed');
    }
}
