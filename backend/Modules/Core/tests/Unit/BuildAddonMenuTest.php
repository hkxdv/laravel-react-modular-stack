<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Modules\Core\Application\Menu\BuildAddonMenu;
use Modules\Core\Tests\Fakes\FakeAddonRegistry;
use Modules\Core\Tests\Fakes\FakePermissionChecker;

uses(Tests\TestCase::class);

// ── CT-MENU-02: log payload has correct module counts ──

it('logs nav_items_build with correct total_modules, included_main, and denied counts', function (): void {
    // Spy on domain_navigation log channel
    $logSpy = Mockery::spy(Psr\Log\LoggerInterface::class);
    Log::shouldReceive('channel')->with('domain_navigation')->andReturn($logSpy);

    $registry = new FakeAddonRegistry();
    $registry->configs = [
        'moda' => [
            'functional_name' => 'Module A',
            'nav_item' => ['show_in_nav' => true, 'show_in_main_nav' => true, 'route_name' => 'moda.index'],
        ],
        'modb' => [
            'functional_name' => 'Module B',
            'nav_item' => ['show_in_nav' => true, 'show_in_main_nav' => true, 'route_name' => 'modb.index'],
        ],
        'modc' => [
            'functional_name' => 'Module C',
            'base_permission' => 'restricted.access',
            'nav_item' => ['show_in_nav' => true, 'show_in_main_nav' => true, 'route_name' => 'modc.index'],
        ],
    ];

    $builder = new BuildAddonMenu($registry);

    // Create fake module objects with getName()
    $modules = [
        new class
        {
            public function getName(): string
            {
                return 'ModA';
            }
        },
        new class
        {
            public function getName(): string
            {
                return 'ModB';
            }
        },
        new class
        {
            public function getName(): string
            {
                return 'ModC';
            }
        },
    ];

    // Deny 'restricted.access' → ModC is denied
    /** @phpstan-ignore argument.type */
    $builder->buildNavItems($modules, FakePermissionChecker::deny('restricted.access'));

    // Assert log payload
    $logSpy->shouldHaveReceived('info')
        ->withArgs(fn (string $message, array $context): bool => $message === 'nav_items_build'
            && $context['total_modules'] === 3
            && $context['included_main'] === 2
            && $context['denied'] === 1);
});
