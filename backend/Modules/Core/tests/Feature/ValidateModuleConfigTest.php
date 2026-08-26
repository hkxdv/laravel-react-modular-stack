<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Modules\Core\Infrastructure\Laravel\Services\ModuleConfigRegistry;

beforeEach(function (): void {
    $this->registry = app()->make(ModuleConfigRegistry::class);
});

it('registers all 3 module configs via boot hook', function (): void {
    expect($this->registry->getAll())->toHaveCount(3)
        ->toHaveKeys(['core', 'admin', 'examples']);
});

it('validates all modules without throwing', function (): void {
    expect(Artisan::call('modules:validate-config'))->toBe(0);
});

it('outputs validation results for modules with findings', function (): void {
    Artisan::call('modules:validate-config');
    $output = Artisan::output();

    // Modules sin findings no aparecen; Examples valida sin warnings tras GAP-MC-4
    expect($output)->not->toContain('[examples]')
        ->and($output)->toContain('All validations passed');
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

it('returns exit code 0 with --strict when no failures exist', function (): void {
    $exitCode = Artisan::call('modules:validate-config', ['--strict' => true]);

    // Examples ahora pasa completamente (GAP-MC-4), sin warnings que --strict pueda elevar
    expect($exitCode)->toBe(0);
});
