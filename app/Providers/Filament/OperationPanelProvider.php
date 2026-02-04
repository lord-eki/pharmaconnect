<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class OperationPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('Operation')
            ->path('operation')
            ->colors([
                'primary' => Color::Red,
            ])->login()
            ->discoverResources(in: app_path('Filament/Operation/Resources'), for: 'App\Filament\Operation\Resources')
            ->discoverPages(in: app_path('Filament/Operation/Pages'), for: 'App\Filament\Operation\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Operation/Widgets'), for: 'App\Filament\Operation\Widgets')
            ->widgets([
                // AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])->resourceCreatePageRedirect('index')->resourceEditPageRedirect('index')->spa()
            ->sidebarWidth('14rem')->databaseNotifications()
            ->font('Roboto')

            ->authMiddleware([
                Authenticate::class,
            ])->viteTheme('resources/css/filament/Operation/theme.css');
    }
}
