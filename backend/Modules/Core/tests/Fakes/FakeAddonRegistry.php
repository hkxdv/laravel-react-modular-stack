<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Fakes;

use App\Interfaces\AuthenticatableUser as User;
use Modules\Core\Contracts\AddonRegistryInterface;
use Modules\Core\Domain\Addon\AddonInstance;
use Nwidart\Modules\Laravel\Module;

/**
 * Fake implementation of AddonRegistryInterface that returns configurable
 * data from a public `$configs` array keyed by module name.
 */
final class FakeAddonRegistry implements AddonRegistryInterface
{
    /** @var array<string, array<string, mixed>> */
    public array $configs = [];

    /** @return array<Module> */
    public function getAvailableAddonsForUser(User $user): array
    {
        return [];
    }

    /** @return array<Module> */
    public function getAccessibleAddons(?User $user = null): array
    {
        return [];
    }

    /** @return array<Module> */
    public function getAllEnabledAddons(): array
    {
        return [];
    }

    public function getAddonConfig(string $moduleName): array
    {
        return $this->configs[$moduleName] ?? [];
    }

    public function getAddonInstance(string $moduleName): ?AddonInstance
    {
        return null;
    }

    /** @return list<AddonInstance> */
    public function getAllEnabledAddonInstances(): array
    {
        return [];
    }

    public function clearConfigCache(): void
    {
        // No-op
    }

    /** @return array<int, array<string, mixed>> */
    public function getGlobalNavItems(?User $user = null): array
    {
        return [];
    }
}
