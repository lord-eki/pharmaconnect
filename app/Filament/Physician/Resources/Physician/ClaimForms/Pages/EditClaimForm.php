<?php

namespace App\Filament\Physician\Resources\Physician\ClaimForms\Pages;

use App\Filament\Physician\Resources\Physician\ClaimForms\ClaimFormResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditClaimForm extends EditRecord
{
    protected static string $resource = ClaimFormResource::class;

   protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            
            Action::make('submit')
                ->label('Submit Claim Form')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->submit();
                    
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Claim form submitted')
                        ->body('The claim form has been submitted for processing.')
                        ->send();
                        
                    return redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn () => $this->record->status === 'draft'),

            DeleteAction::make()
                ->visible(fn () => $this->record->status === 'draft'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
