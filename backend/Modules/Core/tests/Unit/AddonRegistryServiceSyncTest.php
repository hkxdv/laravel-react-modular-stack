<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Modules\Core\Infrastructure\Laravel\Services\AddonRegistryService;
use Modules\Core\Infrastructure\Laravel\Services\ModuleConfigRegistry;

use function Foundry\Helpers\cacheInt;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    config(['cache.default' => 'array']);
});

// ── CT-LISTENER-02: file mtime change triggers nav_version increment ──

it('increments nav_version when modules_statuses.json mtime changes', function (): void {
    // Create a temp file to simulate modules_statuses.json
    $tmpFile = tempnam(sys_get_temp_dir(), 'core_sync_test_');
    file_put_contents($tmpFile, '{}');
    config(['modules.activators.file.statuses-file' => $tmpFile]);

    Cache::store('array')->forever('core.nav_version', 2);

    $service = new AddonRegistryService(new ModuleConfigRegistry());

    // First call → syncModuleStatusesCache detects new file → clearConfigCache → nav_version = 3
    $service->getAllEnabledAddons();

    expect(cacheInt('core.nav_version', 0))->toBe(3);

    // Same mtime → no change
    $service->getAllEnabledAddons();
    expect(cacheInt('core.nav_version', 0))->toBe(3);

    // Change mtime → triggers increment → nav_version = 4
    touch($tmpFile, time() + 100);
    clearstatcache(true, $tmpFile);
    $service->getAllEnabledAddons();

    expect(cacheInt('core.nav_version', 0))->toBe(4);

    @unlink($tmpFile);
});
