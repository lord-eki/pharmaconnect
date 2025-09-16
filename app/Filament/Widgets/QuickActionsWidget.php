<?php

namespace App\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected string $view = 'filament.widgets.quick-actions-widget';

    protected int | string | array $columnSpan = 1;


    

    public function getActions(): array
    {
        return [
            Action::make('user_management')
                ->label('USER MANAGEMENT')
                ->extraAttributes([
                    'class' => 'bg-blue-500 hover:bg-blue-600 text-white rounded-lg p-4 text-left w-full transition-colors duration-200'
                ])
                ->action(fn () => redirect('/admin/users')),

            Action::make('system_config')
                ->label('SYSTEM CONFIG')
                ->extraAttributes([
                    'class' => 'bg-cyan-500 hover:bg-cyan-600 text-white rounded-lg p-4 text-left w-full transition-colors duration-200'
                ])
                ->action(fn () => redirect('/admin/settings')),

            Action::make('reports')
                ->label('REPORTS')
                ->extraAttributes([
                    'class' => 'bg-orange-500 hover:bg-orange-600 text-white rounded-lg p-4 text-left w-full transition-colors duration-200'
                ])
                ->action(fn () => redirect('/admin/reports')),

            Action::make('audit_logs')
                ->label('AUDIT LOGS')
                ->extraAttributes([
                    'class' => 'bg-purple-500 hover:bg-purple-600 text-white rounded-lg p-4 text-left w-full transition-colors duration-200'
                ])
                ->action(fn () => redirect('/admin/audit-logs')),

            Action::make('medicine_database')
                ->label('MEDICINE DATABASE MANAGEMENT')
                ->extraAttributes([
                    'class' => 'bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg p-4 text-left w-full transition-colors duration-200'
                ])
                ->action(fn () => redirect('/admin/medicines')),
        ];
    }
}
