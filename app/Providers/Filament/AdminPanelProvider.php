<?php

namespace App\Providers\Filament;

use App\Models\Tenant;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation;
use Filament\Navigation\NavigationGroup;
use Filament\Pages as FilamentPages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * ID for this panel
     */
    const PANEL_ID = 'admin';


    /**
     * Show Tenant Profile page in tenant dropdown or main menu
     */
    const show_tenant_settings_in_menu = true;

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
            ->tenantConfig()
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
            ->default()
            ->id(self::PANEL_ID)
            ->path(self::PANEL_ID);
        // ->domain(tenancy()->mainDomain());

        return $this;
    }

    private function theme(): static
    {
        $this->panel
            ->maxContentWidth('screen-2xl')
            // ->viteTheme(['resources/filament/css/theme.css'])
            ->colors([
                'primary' => Color::Amber,
                'danger' => Color::Red,
                'info' => Color::Blue,
                'gray' => Color::Slate,
                'success' => Color::Emerald,
                'warning' => Color::Yellow,
            ]);

        return $this;
    }

    private function auth(): static
    {
        $this->panel
            ->login()
            ->registration()
            ->profile()
            ->passwordReset()
            ->emailVerification();

        return $this;
    }

    private function navigation(): static
    {
        $this->panel
            ->globalSearchKeyBindings(['command+k'])
            ->sidebarCollapsibleOnDesktop()

            ->userMenuItems([
                'profile' => Navigation\MenuItem::make()->label('Edit profile'),
                'logout' => Navigation\MenuItem::make()->label('Log out'),
            ])

            ->navigationGroups([
                NavigationGroup::make('Resources'),
                NavigationGroup::make('Access'),
                NavigationGroup::make('Settings'),
            ])

            ->navigationItems([
                Navigation\NavigationItem::make()
                    ->label(FilamentPages\Auth\EditProfile::getNavigationLabel())
                    ->url(fn (): string => FilamentPages\Auth\EditProfile::getUrl())
                    ->group('Settings')
                    // ->group(FilamentPages\Auth\EditProfile::getNavigationGroup())
                    ->icon(FilamentPages\Auth\EditProfile::getNavigationIcon() ?? 'heroicon-o-user-circle'),

            ]);

        return $this;
    }

    private function pages(): static
    {
        $this->panel
            ->discoverResources(in: app_path('Filament/User/Resources'), for: 'App\\Filament\\User\\Resources')
            ->discoverPages(in: app_path('Filament/User/Pages'), for: 'App\\Filament\\User\\Pages')
            ->pages([
                FilamentPages\Dashboard::class,
            ]);

        return $this;
    }

    private function widgets(): static
    {
        $this->panel
            ->discoverWidgets(in: app_path('Filament/Shared/Widgets'), for: 'App\\Filament\\Shared\\Widgets')
            ->discoverWidgets(in: app_path('Filament/User/Widgets'), for: 'App\\Filament\\User\\Widgets')
            ->widgets([]);

        return $this;
    }

    private function tenantConfig(): static
    {
        $this->panel
            ->tenant(Tenant::class, slugAttribute : 'slug', ownershipRelationship: 'tenant')
            ->tenantRoutePrefix('site');
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
                // Set Tenant as soon as user is logged in
                \App\Filament\Tenancy\Middleware\SetDefaultFilamentTenant::class,
            ])
            ->tenantMiddleware([]);

        return $this;
    }

    private function panelBoot(): static
    {
        $this->panel->bootUsing(function (Panel $panel) {
            //
        });

        return $this;
    }
}
