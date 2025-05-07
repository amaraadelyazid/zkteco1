<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Resources\FicheDePaieResource;

class GrhPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('grh')
            ->path('grh')
            ->authGuard('grh') // Use the custom guard
            ->authPasswordBroker('grhs') // Configure the password reset broker
            ->passwordReset() // Enable password reset
            ->default()
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->resources([
                FicheDePaieResource::class,
                \App\Filament\Resources\DemandeCongeResource::class,
            ])
            ->discoverResources(in: app_path('Filament/Grh/Resources'), for: 'App\\Filament\\Grh\\Resources')
            ->discoverPages(in: app_path('Filament/Grh/Pages'), for: 'App\\Filament\\Grh\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Grh/Widgets'), for: 'App\\Filament\\Grh\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
