<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use App\Interfaces\AuthenticatableUser;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Contracts\MenuBuilderInterface;
use Modules\Core\Contracts\NavigationComposerInterface;

use function Foundry\Helpers\cacheInt;
use function Foundry\Helpers\configInt;
use function Foundry\Helpers\configString;
use function Foundry\Helpers\fileModificationTime;
use function Foundry\Helpers\userId;

/**
 * Servicio de composición de elementos de navegación con caché versionada.
 *
 * Extrae la lógica de construcción de claves de caché, Cache::remember
 * y delegación a MenuBuilderInterface::assembleNavigationStructure
 * desde ViewComposerService.
 */
final readonly class NavigationComposer implements NavigationComposerInterface
{
    public function __construct(
        private MenuBuilderInterface $navigationService
    ) {
        //
    }

    /**
     * {@inheritDoc}
     *
     * Aplica caché versionada con claves que incluyen: usuario, módulo,
     * routeSuffix, nav_version, mtime de módulos y perm_version.
     */
    public function composeNavigation(
        string $moduleSlug,
        array $contextualNavItemsConfig,
        callable $permissionChecker,
        ?AuthenticatableUser $user,
        string $functionalName,
        string $routeSuffix,
        array $routeParams,
        array $data
    ): array {
        $prefix = configString('core.cache.nav_cache_prefix', 'core:nav:');
        $versionKey = configString('core.cache.nav_version_key', 'core.nav_version');
        $navVersion = cacheInt($versionKey, 1);
        $ttl = configInt('core.cache.nav_assembled_ttl_seconds', 300);
        $userId = userId($user);
        $permVersion = $userId !== 'anonymous'
          ? cacheInt('user.'.$userId.'.perm_version', 0)
          : 0;
        $modulesStatusesPath = configString(
            'modules.activators.file.statuses-file',
            base_path('modules_statuses.json')
        );
        $modulesMtime = fileModificationTime($modulesStatusesPath);

        $cacheKey = sprintf(
            '%s%s:%s:%s:%d:%d:%d',
            $prefix,
            $userId,
            $moduleSlug,
            $routeSuffix,
            $navVersion,
            $modulesMtime,
            $permVersion
        );

        return Cache::remember(
            $cacheKey,
            $ttl,
            fn (): array => $this->navigationService->assembleNavigationStructure(
                permissionChecker: $permissionChecker,
                moduleSlug: $moduleSlug,
                contextualItemsConfig: $contextualNavItemsConfig,
                user: $user,
                functionalName: $functionalName,
                routeSuffix: $routeSuffix,
                routeParams: $routeParams,
                viewData: $data
            )
        );
    }
}
