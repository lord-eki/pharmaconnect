<?php

namespace App\Filament\Supplier\Resources\Supplier\Quotations\Pages;

use App\Filament\Supplier\Resources\Supplier\Quotations\QuotationResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewQuotation extends ViewRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn ($record) => $record->status === 'pending' && !$record->valid_until->isPast()),

            Action::make('respond')
                ->label('Submit Response')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn ($record) => $record->status === 'pending' && !$record->valid_until->isPast())
                ->requiresConfirmation()
                ->modalHeading('Submit Quotation Response')
                ->modalDescription('Are you sure you want to submit your quotation response? This will send your prices to the requesting physician.')
                ->action(function ($record) {
                    $record->update(['status' => 'sent']);
                    
                    // Placeholder for notification
                    // Notification::send($record->prescription->physician, new QuotationResponseNotification($record));
                })
                ->successNotificationTitle('Quotation response submitted successfully'),
                // ->after(fn (): RedirectResponse => redirect()->route('filament.supplier.resources.quotations.index')),

           Action::make('decline')
                ->label('Decline Request')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn ($record) => $record->status === 'pending')
                ->requiresConfirmation()
                ->modalHeading('Decline Quotation Request')
                ->modalDescription('Are you sure you want to decline this quotation request?')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Reason for Declining')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function ($record, array $data) {
                    $record->update([
                        'status' => 'rejected',
                        'notes' => $data['rejection_reason'],
                    ]);
                    $record->quotationItems()->update(['available' => false]);
                })
                ->successNotificationTitle('Quotation request declined'),
                // ->after(fn () => redirect()->route('filament.supplier.resources.quotations.index')),

            Action::make('print')
                ->label('Print Quotation')
                ->icon('heroicon-o-printer')
                ->color('gray')
                // ->url(fn ($record) => route('quotations.print', $record))
                ->openUrlInNewTab(),
        ];
    }
}
