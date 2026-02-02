<?php

namespace App\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected string $view = 'filament.widgets.quick-actions-widget';
    protected int | string | array $columnSpan = 1;
    protected static ?int $sort = 2;

    public function getActions(): array
    {
        return [
            Action::make('user_management')
                ->label('USER MANAGEMENT')
                ->icon('heroicon-o-user-group')
                ->extraAttributes([
                    'class' => 'bg-blue-500 hover:bg-blue-600 text-white rounded-lg p-4 text-left w-full transition-colors duration-200'
                ])
                ->url(fn () => route('filament.Admin.resources.users.index')),

            Action::make('prescriptions')
                ->label('PRESCRIPTIONS')
                ->icon('heroicon-o-document-text')
                ->extraAttributes([
                    'class' => 'bg-green-500 hover:bg-green-600 text-white rounded-lg p-4 text-left w-full transition-colors duration-200'
                ])
                ->url(fn () => route('filament.Admin.resources.prescriptions.index')),

            Action::make('orders')
                ->label('ORDERS & DELIVERIES')
                ->icon('heroicon-o-shopping-bag')
                ->extraAttributes([
                    'class' => 'bg-orange-500 hover:bg-orange-600 text-white rounded-lg p-4 text-left w-full transition-colors duration-200'
                ])
                ->url(fn () => route('filament.Admin.resources.orders.index')),

            Action::make('medicine_database')
                ->label('MEDICINE DATABASE')
                ->icon('heroicon-o-beaker')
                ->extraAttributes([
                    'class' => 'bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg p-4 text-left w-full transition-colors duration-200'
                ])
                ->url(fn () => route('filament.Admin.resources.medicines.index')),

            Action::make('transactions')
                ->label('FINANCIAL REPORTS')
                ->icon('heroicon-o-currency-dollar')
                ->extraAttributes([
                    'class' => 'bg-cyan-500 hover:bg-cyan-600 text-white rounded-lg p-4 text-left w-full transition-colors duration-200'
                ])
                // ->url(fn () => route('filament.Admin.resources.transactions.index')),
        ];
    }
}