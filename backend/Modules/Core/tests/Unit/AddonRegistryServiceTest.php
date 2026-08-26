<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Modules\Core\Infrastructure\Laravel\Services\AddonRegistryService;
use Modules\Core\Infrastructure\Laravel\Services\ModuleConfigRegistry;

use function Foundry\Helpers\cacheInt;

beforeEach(function (): void {
    config(['cache.default' => 'array']);
});

// ── CT-REG-01: clearConfigCache increments nav_version ──

it('increments nav_version when clearing config cache', function (): void {
    Cache::store('array')->forever('core.nav_version', 5);

    $service = new AddonRegistryService(new ModuleConfigRegistry());
    $service->clearConfigCache();

    expect(cacheInt('core.nav_version', 0))->toBe(6);
});
