<?php

declare(strict_types=1);

namespace Modules\Core\Application\View;

use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Modules\Core\Contracts\AddonRegistryInterface;
use Modules\Core\Contracts\Auth\AuthUserPresenterInterface;
use Modules\Core\Contracts\Auth\AuthUserPresenterRegistryInterface;
use Modules\Core\Contracts\MenuBuilderInterface;
use Modules\Core\Contracts\User\SupportsPasswordAge;
use Modules\Core\Contracts\User\SupportsTwoFactor;
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
        private AuthUserPresenterRegistryInterface $presenterRegistry,
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
        $resolved = $this->presenterRegistry->resolve($request);

        $presenter = $resolved?->presenter;

        $user = $resolved?->user instanceof AbstractDomainUser ? $resolved->user : null;

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
        $presented = $user instanceof AbstractDomainUser && $presenter instanceof AuthUserPresenterInterface
            ? $presenter->present($user)
            : null;

        $transformedUser = $presented;

        $isImpersonating = $user && $request->session()->has('impersonated_by');

        return new AuthPageProps(
            user: $transformedUser,
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
                twoFactorRequired: false,
                twoFactorEnabled: false,
                twoFactorPending: false,
            );
        }

        $guard = $user->getAuthGuard();

        $twoFactorRequired = (bool) config(
            'core.guards.'.$guard.'.two_factor_required',
            config('security.two_factor.'.$guard.'.required', false)
        );

        // Capacidad de 2FA: solo los usuarios que la implementan exponen
        // columnas reales; el resto se resuelve a false/null.
        $capable = $user instanceof SupportsTwoFactor ? $user : null;
        $secretEncrypted = $capable?->twoFactorSecret();
        $confirmedAt = $capable?->twoFactorConfirmedAt();

        $pending = is_string($secretEncrypted)
          && $secretEncrypted !== ''
          && ! $confirmedAt instanceof DateTimeImmutable;

        return new SecurityPageProps(
            twoFactorRequired: $twoFactorRequired,
            twoFactorEnabled: $confirmedAt instanceof DateTimeImmutable,
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
     *
     * Solo aplica a usuarios con la capacidad SupportsPasswordAge; la política
     * de antigüedad se lee por guard desde `core.guards.<guard>.password_max_age_days`
     * con fallback BC a `security.authentication.passwords.<guard>.max_age_days`.
     */
    private function checkPasswordChangeRequired(AbstractDomainUser $user): bool
    {
        if (! $user instanceof SupportsPasswordAge) {
            return false;
        }

        $maxAgeDays = configInt(
            'core.guards.'.$user->getAuthGuard().'.password_max_age_days',
            configInt('security.authentication.passwords.'.$user->getAuthGuard().'.max_age_days', 90)
        );

        $passwordChangedAt = $user->passwordChangedAt();

        if ($passwordChangedAt instanceof DateTimeImmutable) {
            $passwordAge = Date::parse($passwordChangedAt)
                ->diffInDays(Date::now());

            return $passwordAge >= $maxAgeDays;
        }

        return false;
    }
}
