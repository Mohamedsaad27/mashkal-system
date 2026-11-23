<?php

namespace App\Providers\Filament;

use Filament\Enums\ThemeMode;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('نظام إدارة المصحة')
            ->defaultThemeMode(ThemeMode::Light)
            ->globalSearch(false)
            ->font('Almarai', url: asset('fonts/almarai/almarai.css'), provider: LocalFontProvider::class)
            ->colors([
                'primary' => Color::Teal,
            ])
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <style>
                        /* Light mode only: dark sidebar with white text. */
                        html:not(.dark) .fi-sidebar {
                            background-color: var(--primary-900);
                        }
                        html:not(.dark) .fi-sidebar .fi-sidebar-header,
                        html:not(.dark) .fi-sidebar .fi-logo,
                        html:not(.dark) .fi-sidebar .fi-sidebar-item-label,
                        html:not(.dark) .fi-sidebar .fi-sidebar-item-icon {
                            color: #fff;
                        }
                        html:not(.dark) .fi-sidebar .fi-sidebar-header {
                            background-color: var(--primary-900);
                        }
                        html:not(.dark) .fi-sidebar .fi-sidebar-group-label,
                        html:not(.dark) .fi-sidebar .fi-sidebar-group-collapse-btn {
                            color: var(--primary-200);
                        }
                        html:not(.dark) .fi-sidebar .fi-sidebar-item-btn:hover,
                        html:not(.dark) .fi-sidebar .fi-sidebar-item-btn:focus-visible {
                            background-color: rgb(255 255 255 / 0.1);
                        }
                        html:not(.dark) .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
                            background-color: rgb(255 255 255 / 0.18);
                        }
                        html:not(.dark) .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-label,
                        html:not(.dark) .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-icon {
                            color: #fff;
                            font-weight: 700;
                        }
                    </style>
                    HTML),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <script>
                        // Skip the browser's built-in (English) validation popups so the Arabic server messages show instead.
                        document.addEventListener('click', (event) => {
                            const form = event.target.closest?.('form');
                            if (form) form.noValidate = true;
                        }, true);
                    </script>
                    HTML),
            )
            ->navigationGroups([
                'المرضى',
                'الدخل',
                'المصاريف',
                'التقارير',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
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
                Authenticate::class,
            ]);
    }
}
