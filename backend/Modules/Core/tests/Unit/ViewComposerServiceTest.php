<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Modules\Core\Infrastructure\Laravel\Services\NavigationComposer;
use Modules\Core\Infrastructure\Laravel\Services\ViewComposerService;
use Modules\Core\Tests\Fakes\FakeAddonRegistry;
use Modules\Core\Tests\Fakes\FakeMenuBuilder;
use Modules\Core\Tests\Fakes\SessionStoreFake;

uses(SessionStoreFake::class);

beforeEach(function (): void {
    // Force array cache store so cache operations work in tests
    config(['cache.default' => 'array']);

    $this->menuBuilder = new FakeMenuBuilder();
    $this->registry = new FakeAddonRegistry();
    $this->registry->configs = [
        'testmodule' => [
            'functional_name' => 'Test Module',
            'description' => 'A test module',
        ],
    ];
    $navigationComposer = new NavigationComposer($this->menuBuilder);
    $this->service = new ViewComposerService($this->menuBuilder, $this->registry, $navigationComposer);

    // Ensure request has a session for getFlashMessages()
    $this->createFakeSession(request());
});

// ── CT-VIEW-01: cache hit on second identical call ──

it('caches navigation structure and does not re-assemble on second identical call', function (): void {
    $params = [
        'moduleSlug' => 'testmodule',
        'panelItemsConfig' => [],
        'contextualNavItemsConfig' => [],
        'permissionChecker' => fn (string $perm): bool => true,
        'user' => null,
    ];

    $this->service->composeModuleViewContext(...$params);
    $this->service->composeModuleViewContext(...$params);

    expect($this->menuBuilder->assembleCount)->toBe(1);
});

// ── CT-VIEW-02: nav_version bump invalidates cache ──

it('reassembles when nav_version changes between calls', function (): void {
    $params = [
        'moduleSlug' => 'testmodule',
        'panelItemsConfig' => [],
        'contextualNavItemsConfig' => [],
        'permissionChecker' => fn (string $perm): bool => true,
        'user' => null,
    ];

    // First call — populates cache at nav_version = 1 (default)
    $this->service->composeModuleViewContext(...$params);
    expect($this->menuBuilder->assembleCount)->toBe(1);

    // Bump nav_version → cache key changes → second call is a miss
    Cache::store('array')->forever('core.nav_version', 2);
    $this->service->composeModuleViewContext(...$params);

    expect($this->menuBuilder->assembleCount)->toBe(2);
});

// ── CT-VIEW-03: modules_statuses.json mtime change invalidates cache ──

it('reassembles when modules_statuses.json mtime changes', function (): void {
    // Create a temp file and point config to it
    $tmpFile = tempnam(sys_get_temp_dir(), 'core_test_');
    file_put_contents($tmpFile, '{}');
    config(['modules.activators.file.statuses-file' => $tmpFile]);

    $params = [
        'moduleSlug' => 'testmodule',
        'panelItemsConfig' => [],
        'contextualNavItemsConfig' => [],
        'permissionChecker' => fn (string $perm): bool => true,
        'user' => null,
    ];

    // First call — caches with current mtime
    $this->service->composeModuleViewContext(...$params);
    expect($this->menuBuilder->assembleCount)->toBe(1);

    // Change mtime (future timestamp to guarantee difference)
    touch($tmpFile, time() + 100);
    clearstatcache(true, $tmpFile);

    // Second call → mtime changed → cache miss
    $this->service->composeModuleViewContext(...$params);

    expect($this->menuBuilder->assembleCount)->toBe(2);

    @unlink($tmpFile);
});

// ── CT-VIEW-04: result contains all expected navigation keys ──

it('returns result with all required navigation keys as arrays', function (): void {
    $result = $this->service->composeModuleViewContext(
        moduleSlug: 'testmodule',
        panelItemsConfig: [],
        contextualNavItemsConfig: [],
        permissionChecker: fn (string $perm): bool => true,
        user: null,
    );

    expect($result->pageTitle)->toBe('Test Module')
        ->and($result->description)->toBe('A test module');
});
