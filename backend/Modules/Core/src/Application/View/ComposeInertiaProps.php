<?php

declare(strict_types=1);

namespace Modules\Core\Application\View;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Modules\Core\Contracts\AddonRegistryInterface;
use Modules\Core\Contracts\Auth\AuthUserPresenterInterface;
use Modules\Core\Contracts\MenuBuilderInterface;
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
        private AuthUserPresenterInterface $authUserPresenter
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
        /** @var AbstractDomainUser|null $staffUser */
        $staffUser = $request->user('staff');

        $navProps = $this->composeNavigationProps($staffUser);
        $authDto = $this->composeAuthProps($staffUser, $request);
        $securityDto = $this->composeSecurityProps($staffUser);
        $notificationPrefsProps = $this->composeNotificationPreferencesProps($staffUser);

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
     * @return array{breadcrumbs: array<int, array<string, mixed>>, mainNavItems: array<int, array<string, mixed>>, moduleNavItems: array<int, array<string, mixed>>, contextualNavItems: array<int, array<string, mixed>>, globalNavItems: array<int, array<string, mixed>>, passwordChangeRequired: bool}
     */
    private function composeNavigationProps(?AbstractDomainUser $staffUser): array
    {
        if (! $staffUser instanceof AbstractDomainUser) {
            return [
                'breadcrumbs' => [],
                'mainNavItems' => [],
                'moduleNavItems' => [],
                'contextualNavItems' => [],
                'globalNavItems' => [],
                'passwordChangeRequired' => false,
            ];
        }

        $permissionChecker = fn (string $permission): bool => $staffUser->hasPermissionToCross($permission);

        $modules = $this->moduleRegistry->getAvailableAddonsForUser($staffUser);

        $mainNavItems = $this->navigationBuilder->buildNavItems(
            $modules,
            $permissionChecker
        );

        $moduleNavItems = $this->navigationBuilder->buildModuleNavItems(
            $modules,
            $permissionChecker
        );

        // Construir items de navegación global (configuración)
        $globalItemsConfig = $this->moduleRegistry->getGlobalNavItems($staffUser);
        $globalItems = $this->navigationBuilder->buildGlobalNavItems(
            $globalItemsConfig,
            $permissionChecker
        );

        // Verificación de cambio de contraseña
        $passwordChangeRequired = $this->checkPasswordChangeRequired($staffUser);

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
        ?AbstractDomainUser $staffUser,
        Request $request,
    ): AuthPageProps {
        $transformedStaffUser = $staffUser instanceof AbstractDomainUser
          ? $this->authUserPresenter->present($staffUser) : null;

        $isImpersonating = $staffUser && $request->session()->has('impersonated_by');

        return new AuthPageProps(
            user: $transformedStaffUser,
            staff: $transformedStaffUser,
            impersonate: $isImpersonating,
            can: ['impersonate' => $isImpersonating],
        );
    }

    /**
     * Compone las propiedades de seguridad.
     */
    private function composeSecurityProps(?AbstractDomainUser $staffUser): SecurityPageProps
    {
        if (! $staffUser instanceof AbstractDomainUser) {
            return new SecurityPageProps(
                twoFactorRequired: (bool) config('security.two_factor.staff.required', false),
                twoFactorEnabled: false,
                twoFactorPending: false,
            );
        }

        $secretEncrypted = $staffUser->getAttribute('two_factor_secret');
        $confirmedAt = $staffUser->getAttribute('two_factor_confirmed_at');

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
        ?AbstractDomainUser $staffUser
    ): array {
        if (! $staffUser instanceof AbstractDomainUser) {
            return [
                'notificationPreferences' => [],
            ];
        }

        $uid = userId($staffUser);

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
    private function checkPasswordChangeRequired(AbstractDomainUser $staffUser): bool
    {
        $maxAgeDays = configInt(
            'security.authentication.passwords.staff.max_age_days',
            90
        );

        /** @var \Illuminate\Support\Carbon|string|null $passwordChangedAt */
        $passwordChangedAt = $staffUser->getAttribute('password_changed_at');

        if ($passwordChangedAt) {
            $passwordAge = Date::parse($passwordChangedAt)
                ->diffInDays(Date::now());

            return $passwordAge >= $maxAgeDays;
        }

        return false;
    }
}
