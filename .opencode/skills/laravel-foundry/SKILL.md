---
name: laravel-foundry
description: >
  Laravel 13 + nWidart modular monolith with hexagonal Core for foundry-stack.
  Trigger: When working on foundry-stack backend - modules, controllers,
  services, hexagonal Core layer, code quality, Pest tests, or any file
  under backend/app/ or backend/Modules/.
license: MIT
metadata:
  author: foundry-stack
  version: "1.0"
  stack: laravel-13, php-8.4-or-8.5, nwidart-modular, pest-5, phpunit-13, phpstan-max
  project: foundry-stack
---

# Laravel Foundry Stack

Modular monolith for a B2B SaaS (5+ years of life). Two module archetypes coexist:
**Core** (hexagonal pure under `src/`) and **Admin/Examples** (traditional
`app/Http/Controllers` shape). Frontend is React + Vite + Inertia but
inertia shapes the controllers — don't break the shape.

## Stack

| Layer        | Tech                                      | Version / Notes                             |
| ------------ | ----------------------------------------- | ------------------------------------------- |
| Runtime      | PHP                                       | `^8.4 \|\| ^8.5` (forced)                   |
| Framework    | Laravel                                   | `^13.0`                                     |
| Modularity   | nwidart/laravel-modules                   | `>=12.0.4`                                  |
| Front bridge | inertiajs/inertia-laravel                 | `^2.0.10`                                   |
| Permissions  | spatie/laravel-permission                 | `^6.21` (RBAC + cross-guard sync)           |
| Audit        | spatie/laravel-activitylog                | `^4.10.2`                                   |
| DTOs         | spatie/laravel-data                       | `^4.17.1`                                   |
| Tests        | pestphp/pest + phpunit/phpunit            | `^5.1` / `^13`                              |
| Static       | phpstan/phpstan + larastan                | `level: max`                                |
| Refactor     | rector/rector + driftingly/rector-laravel | `^2.x`                                      |
| Format       | laravel/pint                              | `^1.25.1` (preset `laravel` + custom rules) |
| Package mgr  | bun (workspaces)                          | scripts in root `package.json`              |

## Module archetypes

**Core** (hexagonal, only module that uses this):

```
Modules/Core/src/
├── Domain/{User,Menu,Permission,Addon,Stats}/     # NO Laravel imports allowed
├── Application/{Auth,Permissions,Menu,...}/       # use cases, final readonly
├── Contracts/...                                  # interfaces (cross-module comm)
└── Infrastructure/
    ├── Eloquent/Models/                           # adapters
    └── Laravel/{Services,Providers,Facades,...}/  # Laravel-specific
```

**Admin / Examples** (traditional):

```
Modules/<Name>/
├── app/Http/Controllers/                          # one controller per action
├── app/Http/Requests/                             # FormRequest classes
├── app/Interfaces/                                # contracts
├── app/Services/                                  # implementations
└── app/Models/                                    # Eloquent models
```

**PSR-4 namespaces** are configured in each module's `composer.json`:

- Hexagonal: `Modules\Core\` → `src/`
- Traditional: `Modules\<Name>\App\` → `app/` (the `App\` infix is deliberate)

## Naming (English-only in code)

| Type          | Pattern                                          | Example                                                       |
| ------------- | ------------------------------------------------ | ------------------------------------------------------------- |
| Models        | singular PascalCase                              | `StaffUser`, `User`, `Role`                                   |
| Tables        | plural snake_case                                | `users`, `staff_users_login_info`                             |
| Migrations    | `YYYY_MM_DD_HHMMSS_<action>_<table>_table`       | `2025_01_15_120000_create_staff_users_table`                  |
| Controllers   | `VerbEntityController` (one per action/resource) | `ListStaffUsersController`, `EditStaffUserController`         |
| Use cases     | verb in present tense + domain                   | `LoginStaffUser`, `AssembleMenu`, `SyncCrossGuardPermissions` |
| Interfaces    | suffix `Interface`                               | `AddonRegistryInterface`, `MenuBuilderInterface`              |
| Value Objects | noun, no suffix                                  | `DomainUserId`, `AddonInstance`, `EnhancedStat`               |
| Exceptions    | suffix `Exception` or context name               | `InvalidAddonConfig`                                          |
| Module alias  | kebab-case in config                             | `admin`, `core`, `module-01`                                  |
| Routes        | `internal.<guard>.<module>.<resource>.<action>`  | `internal.staff.admin.users.index`                            |

## Code style (Pint rules)

```json
{
  "declare": { "strict_types": true },
  "rules": {
    "declare_strict_types": true,
    "final_class": true,
    "final_internal_class": true,
    "lowercase_keywords": true,
    "visibility_required": true,
    "fully_qualified_strict_types": true,
    "global_namespace_import": { "import_classes": true, "import_constants": true, "import_functions": true },
    "protected_to_private": true,
    "mb_str_functions": true,
    "date_time_immutable": true,
    "modernize_types_casting": true,
    "strict_comparison": true,
    "self_accessor": true,
    "self_static_accessor": true,
    "ordered_class_elements": true,
    "ordered_interfaces": true,
    "ordered_traits": true,
    "no_useless_else": true,
    "no_superfluous_elseif": true,
    "no_multiple_statements_per_line": true,
    "new_with_parentheses": false
  }
}
```

**Required in every PHP file:**

```php
<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers;

// ...
```

**Class rules:**

- `final` by default (use `final readonly class` for DTOs/VOs/use cases)
- Constructor property promotion + strict types always
- One class per file, namespace PSR-4 exact to path
- `self::` over class name (use Rector's `self_accessor` rule)

## Architecture patterns (REQUIRED)

### 1. Hexagonal Core

- `Domain` layer: zero Laravel imports. Pure entities, VOs, collections.
- `Application` layer: use cases = `final readonly class` with `handle()` method.
- `Contracts` layer: interfaces for ALL cross-module communication.
- `Infrastructure`: Eloquent adapters + Laravel-specific implementations.
- Modules consume Core via `Contracts/Services`, never reimplement auth/perms/nav.

### 2. Service/Interface segregation (traditional modules)

- `app/Interfaces/StaffUserManagerInterface.php` defines contract
- `app/Services/AdminStaffUserService.php` implements it
- Controller depends on interface, not concrete service
- Bind in ServiceProvider: `$this->app->bind(Interface::class, Concrete::class)`

### 3. Abstract base controller per module

- `AbstractAdminController` injects `ModuleOrchestratorInterface`, `MenuBuilderInterface`, `StaffUserManagerInterface`
- Exposes `getModuleSlug()` from config

### 4. DTO inmutable + `JsonSerializable`

- `final readonly class` with constructor promotion
- Implements `JsonSerializable` for Inertia without coupling to Laravel's `Arrayable`

### 5. Caching versionado para invalidación

```php
$version = Cache::get("user.{$id}.perm_version", 0);
Cache::put("user.{$id}.perm_version", $version + 1, now()->addDays(7));
```

### 6. Module orchestrator + View composer

- `ModuleOrchestratorInterface::renderModuleView(request, moduleSlug, additionalData, navigationService, view)`
- Centralizes Inertia props across modules

### 7. Service Provider como composition root

- Bind interfaces → concretes
- Register commands, facades, migrations
- `loadMigrationsFrom(module_path('Core', 'database/migrations'))`

### 8. Protected roles

- `ADMIN` and `DEV` cannot be removed via UI
- Service filters and preserves them on `syncRoles()`

### 9. Custom facades via `AliasLoader` (Core only)

- `AliasLoader::getInstance()->alias('Addon', Facade::class)`
- NOT auto-discovery. Register in `CoreServiceProvider::boot()`.

## Testing (Pest 5)

```php
// tests/Pest.php extends Tests\TestCase only in Feature suite
uses(Tests\TestCase::class)
    ->in('Feature', '../Modules/*/tests/Feature', '../Modules/*/tests/Unit');

// tests/Unit/ and tests/Feature/ are global
// Modules/Core/tests/{Unit,Feature}/ are module-specific
```

**PHPUnit 13 env (`phpunit.xml`):**

- `APP_KEY` base64 fake, `CACHE_STORE=array`
- `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`
- `SESSION_DRIVER=array`, `PULSE_ENABLED=false`
- `TELESCOPE_ENABLED=false`, `BCRYPT_ROUNDS=4`
- DB connection commented out → inherits from `.envs/.env.local` (SQLite)

**Pest plugins:**

- `pest-plugin-phpstan` and `pest-plugin-rector` are active in `phpstan.neon` and `rector.php`.
- Agent mode is available through `.opencode/skills/pest-plugin-agent/SKILL.md`.
- Browser testing uses `pest-plugin-browser` with Playwright and requires `ext-sockets`.
- Tia Engine is deferred: PCOV is available locally, but the monorepo Git root does not match `backend/`, which Pest requires for Tia.

## Comandos comunes (Bun wrappers)

```bash
# ÚNICO entrypoint de QA — corre pint:test → test:types → test → rector:dry
bun run be qa

# Backend individual
cd backend && composer pint         # format
cd backend && composer pint:test    # format check
cd backend && composer test         # Pest
cd backend && composer test:types   # PHPStan max
cd backend && composer rector:dry   # Rector dry-run
cd backend && composer rector:fix   # Rector apply

# Crear módulo nuevo
bun run be make-module <Name>

# Frontend
bun run fe dev
bun run fe types
bun run fe lint
```

**NO** corras `vendor/bin/pest`, `vendor/bin/phpstan` sueltos. Usa siempre `bun run be qa` o los scripts composer.

## Laravel Boost MCP

El MCP `laravel-boost` está registrado en `opencode.json` (raíz del repo) y se ejecuta con `php backend/artisan boost:mcp`. Proporciona herramientas accesoribles desde cualquier agente OpenCode que trabaje desde la raíz del monorepo:

- `search-docs` — documentación version-specific de Laravel y paquetes del ecosistema (Inertia, Scout, Pest, etc.). Úsalo **antes** de escribir código Laravel para validar API surface de la versión instalada. Pasa queries broad/topic-based, no nombres de paquetes.
- `database-schema` — inspecciona tablas y columnas antes de escribir migrations o models.
- `database-query` — queries read-only (SELECT/SHOW/EXPLAIN/DESCRIBE) para debugging.
- `tinker` — ejecuta PHP en contexto Laravel para debug rápido. No crear modelos directamente sin aprobación.
- `list-artisan-commands` — verifica parámetros disponibles antes de llamar Artisan.
- `application-info` — versión de PHP, Laravel, DB engine y paquetes instalados.
- `browser-logs` — logs del navegador para debug frontend.
- `last-error` — último error/exception del backend.

**Notas:** El MCP se carga desde la raíz del repo, no desde `backend/`. Los archivos generados por `boost:install` en `backend/` (AGENTS.md, .github/skills/, etc.) fueron eliminados porque asumían un standalone Laravel app y contradecían la arquitectura modular/hexagonal del proyecto. Las guidelines de Laravel Boost no aplican — seguir esta skill y `php-best-practices` en su lugar.
`backend/boost.json` está vacío; no es el registro principal de skills del proyecto.

## Anti-patterns (prohibidos)

- ❌ `StaffUserController` con 7 métodos (un controller por acción)
- ❌ Reimplementar auth/permisos/navegación en módulos feature (usar Core)
- ❌ Imports de Laravel en `Domain/` (cero acoplamiento)
- ❌ `composer pint` desde la raíz sin `cd backend`
- ❌ Asumir `RefreshDatabase` sobre MySQL/Postgres en tests
- ❌ Mezclar "addon" (Core) con "módulo" (nwidart) en docs/código
- ❌ Property hooks PHP 8.4 o asymmetric visibility (no adoptados aún)
- ❌ Readonly classes con propiedades mutables
- ❌ Branch sin sufijo `qN` (rechazado por checklist)
- ❌ `new ClassName()` con paréntesis redundantes (Pint `new_with_parentheses: false`)

## PHPDoc (español técnico neutro)

```php
/**
 * Autentica un staff user y emite la sesión.
 *
 * @param  LoginCredentials  $credentials  Credenciales validadas
 * @return StaffSession                   Sesión activa con token Sanctum
 *
 * @throws InvalidCredentialsException    Si email/password no coinciden
 * @throws AccountLockedException         Si la cuenta está bloqueada
 */
final readonly class LoginStaffUser
{
    public function handle(LoginCredentials $credentials): StaffSession
    {
        // ...
    }
}
```

- 1-2 líneas de descripción
- `@param`, `@return`, `@throws` en todos los métodos públicos
- Ejemplos solo si aclaran comportamiento no obvio
- NO traducir nombres de clases/parámetros al español
