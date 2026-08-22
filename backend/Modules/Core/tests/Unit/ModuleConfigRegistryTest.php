<?php

declare(strict_types=1);

use Modules\Admin\App\ModuleConfig\AdminModuleConfig;
use Modules\Core\Contracts\ModuleConfigInterface;
use Modules\Core\Infrastructure\Laravel\Services\CoreModuleConfig;
use Modules\Core\Infrastructure\Laravel\Services\ModuleConfigRegistry;
use Modules\Examples\App\ModuleConfig\ExamplesModuleConfig;

uses(Tests\TestCase::class);

it('registers implementations keyed by module slug', function (): void {
    $registry = new ModuleConfigRegistry();
    $config = new CoreModuleConfig();

    $registry->register($config);

    expect($registry->getAll())->toHaveKey('core');
});

it('getForModule returns config by slug', function (): void {
    $registry = new ModuleConfigRegistry();
    $config = new CoreModuleConfig();

    $registry->register($config);

    expect($registry->getForModule('core'))->toBeInstanceOf(ModuleConfigInterface::class);
});

it('getForModule returns null for unknown slug', function (): void {
    $registry = new ModuleConfigRegistry();

    expect($registry->getForModule('nonexistent'))->toBeNull();
});

it('getAll returns all registered configs', function (): void {
    $registry = new ModuleConfigRegistry();

    $registry->register(new CoreModuleConfig());
    $registry->register(new AdminModuleConfig());
    $registry->register(new ExamplesModuleConfig());

    expect($registry->getAll())->toHaveCount(3)
        ->toHaveKeys(['core', 'admin', 'examples']);
});
