<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Illuminate\Filesystem\Filesystem;
use Modules\Core\Contracts\ModuleConfigInterface;
use Modules\Core\Domain\Addon\AddonConfig;
use Modules\Core\Domain\Menu\MenuConfigResolver;
use Modules\Core\Domain\Validation\ValidationResult;

/**
 * Valida las configuraciones de módulos contra reglas de integridad.
 */
final readonly class ModuleConfigValidator
{
    public function __construct(
        private ModuleConfigRegistry $registry,
        private PermissionRegistryAggregator $permissionRegistry,
        private Filesystem $filesystem,
    ) {
        //
    }

    /**
     * Valida todos los módulos registrados.
     *
     * @param  bool  $strict  Promueve warnings a failures.
     */
    public function validateAll(bool $strict = false): ValidationResult
    {
        $entries = [];

        foreach ($this->registry->getAll() as $slug => $config) {
            $entries = array_merge($entries, $this->validateModule($slug, $config, $strict));
        }

        return new ValidationResult($entries);
    }

    /**
     * Valida un solo módulo.
     *
     * @return list<array{module: string, rule: string, severity: string, message: string}>
     */
    private function validateModule(
        string $slug,
        ModuleConfigInterface $config,
        bool $strict
    ): array {
        $addon = $config->addon();

        return [
            $this->checkGuard($slug, $addon),
            $this->checkBasePermission($slug, $addon, $strict),
            $this->checkNavRouteName($slug, $config),
            $this->checkDanglingRefs($slug, $config),
            $this->checkFrontendDir($slug, $addon),
            $this->checkInViewDirectoryRequired($slug, $addon),
        ];
    }

    /**
     * @return array{module: string, rule: string, severity: string, message: string}
     */
    private function checkGuard(string $slug, AddonConfig $addon): array
    {
        if ($addon->authGuard === null) {
            return $this->pass($slug, 'guard-exists', 'Null guard — skipped');
        }

        /** @var array<string, mixed> $guards */
        $guards = (array) config('auth.guards', []);

        return $addon->isValidGuard(array_keys($guards))
            ? $this->pass($slug, 'guard-exists', sprintf("Guard '%s' is valid", $addon->authGuard))
            : $this->fail($slug, 'guard-exists', sprintf("Guard '%s' not found in config('auth.guards')", $addon->authGuard));
    }

    /**
     * @return array{module: string, rule: string, severity: string, message: string}
     */
    private function checkBasePermission(
        string $slug,
        AddonConfig $addon,
        bool $strict
    ): array {
        if ($addon->basePermission === null) {
            return $this->pass($slug, 'base-permission', 'Null permission — skipped');
        }

        $grouped = $this->permissionRegistry->getGroupedByModule();
        // PermissionRegistryAggregator keys by moduleName() (StudlyCase).
        // Match against slug by finding the entry whose lowercased name matches.
        /** @var list<array{name: string, description: string, guard: string}> $modulePerms */
        $modulePerms = [];
        foreach ($grouped as $key => $perms) {
            if (mb_strtolower($key) === $slug) {
                $modulePerms = $perms;

                break;
            }
        }

        $names = array_column($modulePerms, 'name');

        if (in_array($addon->basePermission, $names, true)) {
            return $this->pass($slug, 'base-permission', sprintf("Permission '%s' declared", $addon->basePermission));
        }

        $severity = $strict ? 'fail' : 'warn';

        return [
            'module' => $slug,
            'rule' => 'base-permission',
            'severity' => $severity,
            'message' => sprintf("Permission '%s' not declared in PermissionRegistry", $addon->basePermission),
        ];
    }

    /**
     * @return array{module: string, rule: string, severity: string, message: string}
     */
    private function checkNavRouteName(string $slug, ModuleConfigInterface $config): array
    {
        $nav = $config->navItem();

        if (! $nav instanceof \Modules\Core\Domain\Menu\NavItem) {
            return $this->pass($slug, 'nav-route-name', 'No navItem — skipped');
        }

        if (! $nav->showInNav) {
            return $this->pass($slug, 'nav-route-name', 'show_in_nav is false — skipped');
        }

        $routeName = $nav->routeNameSuffix;
        if ($routeName !== '') {
            return $this->pass($slug, 'nav-route-name', sprintf("route_name '%s' is valid", $routeName));
        }

        return $this->fail($slug, 'nav-route-name', 'route_name must be non-empty when show_in_nav is true');
    }

    /**
     * @return array{module: string, rule: string, severity: string, message: string}
     */
    private function checkDanglingRefs(string $slug, ModuleConfigInterface $config): array
    {
        $resolver = new MenuConfigResolver();

        // ponytail: scanned via toArray() for belt-and-suspenders $ref leak detection.
        // DTOs are pre-resolved during construction, so this rarely finds anything.
        $arrays = [
            'contextual_nav' => $config->contextualNav()->toArray(),
            'breadcrumbs' => $config->breadcrumbs()->toArray(),
            'panel_items' => $config->panelItems(),
        ];

        foreach ($arrays as $key => $arr) {
            if ($arr === []) {
                continue;
            }

            /** @var array<string, mixed> $rawConfig */
            $rawConfig = (array) config($slug, []);

            /** @var array<mixed> $resolved */
            $resolved = $resolver->resolve($arr, $rawConfig);
            $dangling = $this->findDanglingRefs($resolved);

            if ($dangling !== []) {
                return $this->fail(
                    $slug,
                    'dangling-ref',
                    'Unresolved $ref in '.$key.': '.implode(', ', $dangling)
                );
            }
        }

        return $this->pass($slug, 'dangling-ref', 'No unresolved $ref strings');
    }

    /**
     * @return list<string>
     */
    private function findDanglingRefs(mixed $data): array
    {
        if (is_string($data) && str_starts_with($data, '$ref:')) {
            return [$data];
        }

        if (! is_array($data)) {
            return [];
        }

        $refs = [];
        foreach ($data as $value) {
            $refs = array_merge($refs, $this->findDanglingRefs($value));
        }

        return $refs;
    }

    /**
     * @return array{module: string, rule: string, severity: string, message: string}
     */
    private function checkFrontendDir(string $slug, AddonConfig $addon): array
    {
        // Core exemption: modules with null base_permission are not panel modules
        if ($addon->basePermission === null) {
            return $this->pass($slug, 'frontend-dir', 'Non-panel module (base_permission=null) — skipped');
        }

        $viewDir = $addon->inertiaViewDirectory;
        /** @var string $frontendPath */
        $frontendPath = config('module-config.frontend_path', 'frontend/src/pages/modules');
        // Resolve relative to project root (monorepo layout), not backend base_path()
        $rootPath = dirname(base_path());
        $fullPath = $rootPath.'/'.$frontendPath.'/'.$viewDir;

        if ($this->filesystem->isDirectory($fullPath)) {
            return $this->pass($slug, 'frontend-dir', 'Directory exists: '.$fullPath);
        }

        return $this->fail($slug, 'frontend-dir', 'Directory not found: '.$fullPath);
    }

    /**
     * @return array{module: string, rule: string, severity: string, message: string}
     */
    private function checkInViewDirectoryRequired(string $slug, AddonConfig $addon): array
    {
        // Core exemption: modules with null base_permission are not panel modules
        if ($addon->basePermission === null) {
            return $this->pass($slug, 'inertia-view-directory-required', 'Non-panel module (base_permission=null) — skipped');
        }

        /** @var array<string, mixed> $raw */
        $raw = $addon->raw;
        $declared = $raw['inertia_view_directory'] ?? null;

        if (is_string($declared) && $declared !== '') {
            return $this->pass($slug, 'inertia-view-directory-required', sprintf("Declared: '%s'", $declared));
        }

        return $this->fail($slug, 'inertia-view-directory-required', 'inertia_view_directory must be explicitly declared in config');
    }

    /**
     * @return array{module: string, rule: string, severity: string, message: string}
     */
    private function pass(string $slug, string $rule, string $message): array
    {
        return [
            'module' => $slug,
            'rule' => $rule,
            'severity' => 'pass',
            'message' => $message,
        ];
    }

    /**
     * @return array{module: string, rule: string, severity: string, message: string}
     */
    private function fail(string $slug, string $rule, string $message): array
    {
        return [
            'module' => $slug,
            'rule' => $rule,
            'severity' => 'fail',
            'message' => $message,
        ];
    }
}
