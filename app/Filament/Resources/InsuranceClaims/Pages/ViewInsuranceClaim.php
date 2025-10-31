<?php

namespace App\Filament\Resources\InsuranceClaims\Pages;

use App\Filament\Resources\InsuranceClaims\InsuranceClaimResource;
use App\Services\InsuranceClaimPDFService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;

class ViewInsuranceClaim extends ViewRecord
{
    protected static string $resource = InsuranceClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            // Download Claim Form
            Action::make('download_claim')
                ->label('Download Claim Form')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn () => InsuranceClaimPDFService::download($this->record)),

            // View Claim Form
            Action::make('view_claim')
                ->label('View in Browser')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn () => route('insurance-claims.pdf', $this->record))
                ->openUrlInNewTab(),

            // Approve Claim (for insurers)
            Action::make('approve')
                ->label('Approve Claim')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->canBeApproved())
                ->requiresConfirmation()
                ->form([
                    TextInput::make('approved_amount')
                        ->label('Approved Amount')
                        ->numeric()
                        ->prefix('KES')
                        ->required()
                        ->default(fn () => $this->record->claimed_amount),
                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->approve($data['approved_amount'], $data['notes']);

                    \Filament\Notifications\Notification::make()
                        ->title('Claim Approved')
                        ->success()
                        ->send();
                }),

            // Reject Claim
            Action::make('reject')
                ->label('Reject Claim')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->canBeRejected())
                ->requiresConfirmation()
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->reject($data['rejection_reason']);

                    \Filament\Notifications\Notification::make()
                        ->title('Claim Rejected')
                        ->danger()
                        ->send();
                }),

            DeleteAction::make(),        ];
    }
}
