<?php

namespace App\Providers\Filament;

use App\Http\Middleware\AccessTokenValidationMiddleware;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ConventionPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('convention')
            ->path('convention')
            ->authGuard('web')
            ->brandName('Convention Manager')
            ->brandLogo(asset('images/ef-logo.svg'))
            ->darkModeBrandLogo(asset('images/ef-logo-dark.svg'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/ef-logo.svg'))
            ->login()
            ->topNavigation()
            ->colors([
                'primary' => Color::generateV3Palette('#00504b'),
            ])
            ->discoverResources(in: app_path('Filament/Convention/Resources'), for: 'App\\Filament\\Convention\\Resources')
            ->discoverPages(in: app_path('Filament/Convention/Pages'), for: 'App\\Filament\\Convention\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                FilamentAuthenticate::class,
                AccessTokenValidationMiddleware::class,
            ]);
    }
}
