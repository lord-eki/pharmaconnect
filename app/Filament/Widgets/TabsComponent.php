<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class TabsComponent extends Widget
{
    protected string $view = 'filament.widgets.tabs-component';
    protected int|string|array $columnSpan = 'full';
    
    public string $activeTab = 'users';

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getTabContent(): array
    {
        return [
            'users' => [
                'title' => 'Users Management',
                'content' => 'Manage system users, roles, and permissions here.'
            ],
            'financial' => [
                'title' => 'Financial Management',
                'content' => 'Handle transactions, payments, and financial reports.'
            ],
            'pricing' => [
                'title' => 'Pricing Configuration',
                'content' => 'Configure pricing rules, markups, and discounts.'
            ],
            'system' => [
                'title' => 'System Settings',
                'content' => 'Manage system configuration and settings.'
            ]
        ];
    }
}