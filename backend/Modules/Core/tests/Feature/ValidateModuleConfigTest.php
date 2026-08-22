<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Modules\Core\Infrastructure\Laravel\Services\ModuleConfigRegistry;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    $this->registry = app()->make(ModuleConfigRegistry::class);
});

it('registers all 3 module configs via boot hook', function (): void {
    expect($this->registry->getAll())->toHaveCount(3)
        ->toHaveKeys(['core', 'admin', 'examples']);
});

it('validates all modules without throwing', function (): void {
    $exitCode = Artisan::call('modules:validate-config');

    expect($exitCode)->toBeInt();
});

it('outputs validation results for modules with findings', function (): void {
    Artisan::call('modules:validate-config');
    $output = Artisan::output();

    // Modules sin findings no aparecen; Examples siempre genera el WARN legacy
    expect($output)->toContain('[examples]')
        ->toContain('access-examples');
});

it('detects base-permission warning for Examples (access-examples not in registry)', function (): void {
    Artisan::call('modules:validate-config');
    $output = Artisan::output();

    expect($output)->toContain('access-examples');
});

it('returns exit code 0 when only warnings exist (no failures)', function (): void {
    $exitCode = Artisan::call('modules:validate-config');

    // Admin ahora declara inertia_view_directory; solo queda el WARN de Examples
    expect($exitCode)->toBe(0);
});

it('detects missing inertia_view_directory when a module omits it', function (): void {
    // Simula el faltante que el validador debe atrapar
    config()->set('admin.inertia_view_directory');

    $exitCode = Artisan::call('modules:validate-config');
    $output = Artisan::output();

    expect($exitCode)->toBe(1)
        ->and($output)->toContain('[admin]')
        ->and($output)->toContain('inertia');
});

it('returns exit code 1 with --strict (warnings become failures)', function (): void {
    $exitCode = Artisan::call('modules:validate-config', ['--strict' => true]);

    expect($exitCode)->toBe(1);
});
