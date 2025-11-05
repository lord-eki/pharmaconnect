<?php

namespace App\Providers\Filament;

use App\Filament\Physician\Pages\TrackPage;
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

class PhysicianPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('Physician')
            ->path('physician')
            ->colors([
                'primary' => Color::Green,
            ])->login()
            ->discoverResources(in: app_path('Filament/Physician/Resources'), for: 'App\Filament\Physician\Resources')
            ->discoverPages(in: app_path('Filament/Physician/Pages'), for: 'App\Filament\Physician\Pages')
            ->pages([
                Dashboard::class,
                TrackPage::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Physician/Widgets'), for: 'App\Filament\Physician\Widgets')
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
            ])->resourceCreatePageRedirect('index')->resourceEditPageRedirect('index')
            ->sidebarWidth('17rem')->spa()->topNavigation()->databaseNotifications()
            ->font('Lato')
            ->authMiddleware([
                Authenticate::class,
            ]);
    }


}
