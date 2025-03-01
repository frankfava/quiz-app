<?php

namespace App\Providers\Filament;

use App\Filament\User\Pages;
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
            ->path(self::PANEL_ID)
            ->domain(tenancy()->mainDomain());

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
            // ->registration(action : Pages\Auth\Register::class)
            ->profile(page : Pages\Auth\EditProfile::class)
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
                // View Site
                Navigation\NavigationItem::make()
                    ->label('View your Site')
                    ->url(fn () => Filament::getTenant()->url, true)
                    ->icon('heroicon-o-globe-alt'),

                Navigation\NavigationItem::make()
                    ->label(Pages\Auth\EditProfile::getNavigationLabel())
                    ->url(fn (): string => Pages\Auth\EditProfile::getUrl())
                    ->group(Pages\Auth\EditProfile::getNavigationGroup())
                    ->icon(Pages\Auth\EditProfile::getNavigationIcon()),

                // Tenant Profile/ Settings
                Navigation\NavigationItem::make()
                    ->visible(function (): bool {
                        return self::show_tenant_settings_in_menu &&
                            Pages\Tenancy\EditTenantProfile::canView(Filament::getTenant());
                    })
                    ->label(Pages\Tenancy\EditTenantProfile::getNavigationLabel())
                    ->url(fn (): string => Pages\Tenancy\EditTenantProfile::getUrl())
                    ->group(Pages\Tenancy\EditTenantProfile::getNavigationGroup())
                    ->icon(Pages\Tenancy\EditTenantProfile::getNavigationIcon()),
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
            ->tenantRoutePrefix('site')
            ->tenantProfile(Pages\Tenancy\EditTenantProfile::class)
            ->tenantRegistration(Pages\Tenancy\RegisterTenant::class) // Show by Policy
            ->tenantMenuItems([
                'profile' => Navigation\MenuItem::make()
                    ->hidden(self::show_tenant_settings_in_menu)
                    ->label(Pages\Tenancy\EditTenantProfile::getNavigationLabel())
                    ->icon(Pages\Tenancy\EditTenantProfile::getNavigationIcon()),
                'register' => Navigation\MenuItem::make()
                    ->label(Pages\Tenancy\RegisterTenant::getLabel())
                    ->icon(Pages\Tenancy\RegisterTenant::$navigationIcon),
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
