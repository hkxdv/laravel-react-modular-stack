<?php

declare(strict_types=1);

namespace Modules\Core\Application\View;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Modules\Core\Contracts\AddonRegistryInterface;
use Modules\Core\Contracts\Auth\AuthUserPresenterInterface;
use Modules\Core\Contracts\Auth\AuthUserPresenterResolverInterface;
use Modules\Core\Contracts\MenuBuilderInterface;
use Modules\Core\Domain\Menu\ResolvedBreadcrumbItem;
use Modules\Core\Domain\Menu\ResolvedNavItem;
use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;

use function Foundry\Helpers\cacheArray;
use function Foundry\Helpers\configInt;
use function Foundry\Helpers\userId;

/**
 * Acción para componer las props compartidas de Inertia.
 */
final readonly class ComposeInertiaProps
{
    public function __construct(
        private AddonRegistryInterface $moduleRegistry,
        private MenuBuilderInterface $navigationBuilder,
        private AuthUserPresenterResolverInterface $presenterResolver,
    ) {
        //
    }

    /**
     * Ejecuta la composición de props para Inertia.
     *
     * @param  Request  $request  Petición actual.
     */
    public function execute(Request $request): GlobalPageProps
    {
        $presenter = $this->presenterResolver->resolve($request);

        /** @var AbstractDomainUser|null $user */
        $user = $request->user('staff') ?? $request->user('tenant');

        $navProps = $this->composeNavigationProps($user);
        $authDto = $this->composeAuthProps($user, $request, $presenter);
        $securityDto = $this->composeSecurityProps($user);
        $notificationPrefsProps = $this->composeNotificationPreferencesProps($user);

        return new GlobalPageProps(
            breadcrumbs: $navProps['breadcrumbs'],
            mainNavItems: $navProps['mainNavItems'],
            moduleNavItems: $navProps['moduleNavItems'],
            contextualNavItems: $navProps['contextualNavItems'],
            globalNavItems: $navProps['globalNavItems'],
            passwordChangeRequired: $navProps['passwordChangeRequired'],
            auth: $authDto,
            security: $securityDto,
            notificationPreferences: $notificationPrefsProps['notificationPreferences'],
        );
    }

    /**
     * Compone las propiedades de navegación.
     *
     * @return array{breadcrumbs: list<ResolvedBreadcrumbItem>, mainNavItems: list<ResolvedNavItem>, moduleNavItems: list<ResolvedNavItem>, contextualNavItems: list<ResolvedNavItem>, globalNavItems: list<ResolvedNavItem>, passwordChangeRequired: bool}
     */
    private function composeNavigationProps(?AbstractDomainUser $user): array
    {
        if (! $user instanceof AbstractDomainUser) {
            return [
                'breadcrumbs' => [],
                'mainNavItems' => [],
                'moduleNavItems' => [],
                'contextualNavItems' => [],
                'globalNavItems' => [],
                'passwordChangeRequired' => false,
            ];
        }

        $permissionChecker = fn (string $permission): bool => $user->hasPermissionToCross($permission);

        $modules = $this->moduleRegistry->getAvailableAddonsForUser($user);

        $mainNavItems = $this->navigationBuilder->buildNavItems(
            $modules,
            $permissionChecker
        );

        $moduleNavItems = $this->navigationBuilder->buildModuleNavItems(
            $modules,
            $permissionChecker
        );

        // Construir items de navegación global (configuración)
        $globalItemsConfig = $this->moduleRegistry->getGlobalNavItems($user);
        $globalItems = $this->navigationBuilder->buildGlobalNavItems(
            $globalItemsConfig,
            $permissionChecker
        );

        // Verificación de cambio de contraseña
        $passwordChangeRequired = $this->checkPasswordChangeRequired($user);

        return [
            'breadcrumbs' => [],
            'mainNavItems' => $mainNavItems,
            'moduleNavItems' => $moduleNavItems,
            'contextualNavItems' => [],
            'globalNavItems' => $globalItems,
            'passwordChangeRequired' => $passwordChangeRequired,
        ];
    }

    /**
     * Compone las propiedades de autenticación.
     */
    private function composeAuthProps(
        ?AbstractDomainUser $user,
        Request $request,
        ?AuthUserPresenterInterface $presenter,
    ): AuthPageProps {
        /** @var \Modules\Core\Domain\User\DTO\StaffUserDto|\Modules\Core\Domain\User\DTO\TenantUserDto|array<never, never>|null $presented */
        $presented = $user instanceof AbstractDomainUser && $presenter instanceof AuthUserPresenterInterface
          ? $presenter->present($user) : null;

        // Presenter returns [] for unsupported user types; treat as null
        if (is_array($presented) && $presented === []) {
            $presented = null;
        }

        /** @var \Modules\Core\Domain\User\DTO\StaffUserDto|\Modules\Core\Domain\User\DTO\TenantUserDto|null */
        $transformedUser = $presented;

        $isImpersonating = $user && $request->session()->has('impersonated_by');

        return new AuthPageProps(
            user: $transformedUser,
            staff: $transformedUser,
            impersonate: $isImpersonating,
            can: ['impersonate' => $isImpersonating],
        );
    }

    /**
     * Compone las propiedades de seguridad.
     */
    private function composeSecurityProps(?AbstractDomainUser $user): SecurityPageProps
    {
        if (! $user instanceof AbstractDomainUser) {
            return new SecurityPageProps(
                twoFactorRequired: (bool) config('security.two_factor.staff.required', false),
                twoFactorEnabled: false,
                twoFactorPending: false,
            );
        }

        $secretEncrypted = $user->getAttribute('two_factor_secret');
        $confirmedAt = $user->getAttribute('two_factor_confirmed_at');

        $pending = is_string($secretEncrypted)
          && $secretEncrypted !== ''
          && $confirmedAt === null;

        return new SecurityPageProps(
            twoFactorRequired: (bool) config('security.two_factor.staff.required', false),
            twoFactorEnabled: $confirmedAt !== null,
            twoFactorPending: $pending,
        );
    }

    /**
     * Compone las preferencias de notificación.
     *
     * @return array{notificationPreferences: array<string, mixed>}
     */
    private function composeNotificationPreferencesProps(
        ?AbstractDomainUser $user
    ): array {
        if (! $user instanceof AbstractDomainUser) {
            return [
                'notificationPreferences' => [],
            ];
        }

        $uid = userId($user);

        if ($uid === 'anonymous') {
            return [
                'notificationPreferences' => [],
            ];
        }

        return [
            'notificationPreferences' => cacheArray('user.'.$uid.'.notification_preferences'),
        ];
    }

    /**
     * Verifica si se requiere cambio de contraseña.
     */
    private function checkPasswordChangeRequired(AbstractDomainUser $user): bool
    {
        $maxAgeDays = configInt(
            'security.authentication.passwords.staff.max_age_days',
            90
        );

        /** @var \Illuminate\Support\Carbon|string|null $passwordChangedAt */
        $passwordChangedAt = $user->getAttribute('password_changed_at');

        if ($passwordChangedAt) {
            $passwordAge = Date::parse($passwordChangedAt)
                ->diffInDays(Date::now());

            return $passwordAge >= $maxAgeDays;
        }

        return false;
    }
}
