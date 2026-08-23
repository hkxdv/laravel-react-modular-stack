<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Modules\Core\Contracts\ModuleConfigInterface;
use Modules\Core\Domain\Addon\AddonConfig;
use Modules\Core\Domain\Menu\BreadcrumbItem;
use Modules\Core\Domain\Menu\BreadcrumbMap;
use Modules\Core\Domain\Menu\ContextualNavMap;
use Modules\Core\Domain\Menu\NavComponentGroup;
use Modules\Core\Domain\Menu\NavComponentLink;
use Modules\Core\Domain\Menu\NavItem;
use Modules\Core\Domain\Panel\PanelItem;

/**
 * Configuración declarativa del módulo Core.
 */
final class CoreModuleConfig implements ModuleConfigInterface
{
    public function addon(): AddonConfig
    {
        /** @var array<string, mixed> $config */
        $config = (array) config('core');

        return AddonConfig::fromArray('Core', $config);
    }

    public function navItem(): ?NavItem
    {
        return null;
    }

    public function contextualNav(): ContextualNavMap
    {
        $profile = $this->buildProfileLink();
        $password = $this->buildPasswordLink();
        $appearance = $this->buildAppearanceLink();
        $security = $this->buildSecurityLink();
        $notifications = $this->buildNotificationsLink();

        /** @var array<string, list<NavComponentLink|NavComponentGroup>> $items */
        $items = [
            'default' => [
                new NavComponentGroup(
                    name: 'user_profile_nav',
                    links: [$profile, $password, $appearance, $security, $notifications]
                ),
            ],
        ];

        return ContextualNavMap::of($items);
    }

    public function breadcrumbs(): BreadcrumbMap
    {
        /** @var array<string, list<BreadcrumbItem>> $items */
        $items = [
            'profile.edit' => [
                $this->buildBreadcrumbConfigRoot(),
                $this->buildBreadcrumbProfile(),
            ],
            'password.edit' => [
                $this->buildBreadcrumbConfigRoot(),
                $this->buildBreadcrumbPassword(),
            ],
            'appearance' => [
                $this->buildBreadcrumbConfigRoot(),
                $this->buildBreadcrumbAppearance(),
            ],
            'security.edit' => [
                $this->buildBreadcrumbConfigRoot(),
                $this->buildBreadcrumbSecurity(),
            ],
            'notifications.edit' => [
                $this->buildBreadcrumbConfigRoot(),
                $this->buildBreadcrumbNotifications(),
            ],
        ];

        return new BreadcrumbMap($items);
    }

    /** @return list<PanelItem> */
    public function panelItems(): array
    {
        return [];
    }

    // ── Nav component links (shared definitions) ──

    private function buildProfileLink(): NavComponentLink
    {
        return new NavComponentLink(
            key: 'profile',
            title: 'Perfil',
            routeNameSuffix: 'internal.staff.profile.edit',
            icon: 'UserCog',
        );
    }

    private function buildPasswordLink(): NavComponentLink
    {
        return new NavComponentLink(
            key: 'password',
            title: 'Contraseña',
            routeNameSuffix: 'internal.staff.password.edit',
            icon: 'KeyRound',
        );
    }

    private function buildAppearanceLink(): NavComponentLink
    {
        return new NavComponentLink(
            key: 'appearance',
            title: 'Apariencia',
            routeNameSuffix: 'internal.staff.appearance',
            icon: 'Palette',
        );
    }

    private function buildSecurityLink(): NavComponentLink
    {
        return new NavComponentLink(
            key: 'account_security',
            title: 'Seguridad',
            routeNameSuffix: 'internal.staff.security.edit',
            icon: 'Shield',
        );
    }

    private function buildNotificationsLink(): NavComponentLink
    {
        return new NavComponentLink(
            key: 'notification_preferences',
            title: 'Notificaciones',
            routeNameSuffix: 'internal.staff.notifications.edit',
            icon: 'Bell',
        );
    }

    // ── Breadcrumb components (shared definitions) ──

    private function buildBreadcrumbConfigRoot(): BreadcrumbItem
    {
        return new BreadcrumbItem(
            title: 'Configuración',
            routeNameSuffix: 'internal.staff.profile.edit',
        );
    }

    private function buildBreadcrumbProfile(): BreadcrumbItem
    {
        return new BreadcrumbItem(
            title: 'Perfil',
            routeNameSuffix: 'internal.staff.profile.edit',
        );
    }

    private function buildBreadcrumbPassword(): BreadcrumbItem
    {
        return new BreadcrumbItem(
            title: 'Contraseña',
            routeNameSuffix: 'internal.staff.password.edit',
        );
    }

    private function buildBreadcrumbAppearance(): BreadcrumbItem
    {
        return new BreadcrumbItem(
            title: 'Apariencia',
            routeNameSuffix: 'internal.staff.appearance',
        );
    }

    private function buildBreadcrumbSecurity(): BreadcrumbItem
    {
        return new BreadcrumbItem(
            title: 'Seguridad',
            routeNameSuffix: 'internal.staff.security.edit',
        );
    }

    private function buildBreadcrumbNotifications(): BreadcrumbItem
    {
        return new BreadcrumbItem(
            title: 'Notificaciones',
            routeNameSuffix: 'internal.staff.notifications.edit',
        );
    }
}
