<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages as FilamentPages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SuperUserPanelProvider extends PanelProvider
{
    /**
     * ID for this panel
     */
    const PANEL_ID = 'superadmin';

    /**
     * $panel
     *
     * @var Panel
     */
    private $panel;

    public function panel(Panel $panel): Panel
    {
        $this->panel = $panel;

        // Apply Config
        $this
            ->setup()
            ->theme()
            ->auth()
            ->navigation()
            ->pages()
            ->widgets()
            ->middleware()
            ->panelBoot();

        return $this->panel;

    }

    private function setup(): static
    {
        $this->panel
            ->id(self::PANEL_ID)
            ->path(self::PANEL_ID)
            ->domain(tenancy()->mainDomain());

        return $this;
    }

    private function theme(): static
    {
        $this->panel
            ->maxContentWidth('screen-2xl')
            ->viteTheme(['resources/filament/css/theme.css', 'resources/filament/js/filament.js'])
            ->colors([
                'primary' => Color::Slate,
                'danger' => Color::Red,
                'info' => Color::Blue,
                'gray' => Color::Slate,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ]);

        return $this;
    }

    private function auth(): static
    {
        $this->panel
            ->login()
            ->authGuard('superuser')
            ->authPasswordBroker('super_users')
            ->passwordReset();

        return $this;
    }

    private function navigation(): static
    {
        $this->panel
            ->globalSearchKeyBindings(['command+k'])
            ->navigationGroups([
                NavigationGroup::make('Resources'),
                NavigationGroup::make('Tools'),
                NavigationGroup::make('Users'),
            ]);

        return $this;
    }

    private function pages(): static
    {
        $this->panel
            ->discoverResources(in: app_path('Filament/SuperUser/Resources'), for: 'App\\Filament\\SuperUser\\Resources')
            ->discoverPages(in: app_path('Filament/SuperUser/Pages'), for: 'App\\Filament\\SuperUser\\Pages')
            ->pages([
                FilamentPages\Dashboard::class,
            ]);

        return $this;
    }

    private function widgets(): static
    {
        $this->panel
            ->discoverWidgets(in: app_path('Filament/Shared/Widgets'), for: 'App\\Filament\\Shared\\Widgets')
            ->discoverWidgets(in: app_path('Filament/SuperUser/Widgets'), for: 'App\\Filament\\SuperUser\\Widgets')
            ->widgets([
                // Widgets\FilamentInfoWidget::class,
            ]);

        return $this;
    }

    private function middleware(): static
    {
        $this->panel
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

        return $this;
    }

    private function panelBoot(): static
    {
        $this->panel->bootUsing(function (Panel $panel) {});

        return $this;
    }
}
