<?php

namespace App\Providers\Filament;

use App\Http\Responses\CustomLoginResponse;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class InsurerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('Insurer')
            ->path('insurer')
            ->colors([
                'primary' => Color::Indigo,
            ])->login()
            ->discoverResources(in: app_path('Filament/Insurer/Resources'), for: 'App\Filament\Insurer\Resources')
            ->discoverPages(in: app_path('Filament/Insurer/Pages'), for: 'App\Filament\Insurer\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Insurer/Widgets'), for: 'App\Filament\Insurer\Widgets')
            ->widgets([
                AccountWidget::class,
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
            ])->resourceCreatePageRedirect('index')->resourceEditPageRedirect('index')
            ->spa()->databaseNotifications()
            ->authMiddleware([
                Authenticate::class,
            ]);
    }


}
