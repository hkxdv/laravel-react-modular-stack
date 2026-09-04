# Changelog

Todos los cambios notables de Foundry Stack se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

> [!NOTE]
> Este proyecto aún no tiene releases/tags publicados. Este changelog documenta hitos del repositorio y se irá refinando cuando existan releases formales.

## [Unreleased]

### Added

- Wayfinder (`laravel/wayfinder` + `@laravel/vite-plugin-wayfinder`): rutas y acciones tipadas generadas desde el backend hacia `frontend/src/{actions,routes,wayfinder}` (gitignored); resolver dinámico `src/lib/routing.ts` para nombres de ruta provenientes del backend (nav, breadcrumbs, server tables); `formVariants` habilitado.
- Challenge de 2FA durante el login: 2FA TOTP propio de Core completado (staging en `AbstractLoginRequest`, use-case `VerifyLoginChallenge` con TOTP o recovery code de un solo uso, página `auth/two-factor-challenge`, middleware `2fa` con policy por guard `core.guards.{guard}.two_factor_required`, default `false`).
- Passkeys (WebAuthn) para el guard `staff` vía `laravel/passkeys` standalone: `StaffUser` implementa `PasskeyUser`, hook `authorizeLoginUsing` con checks de seguridad (cuenta activa + blocklist/attempts por IP) antes de establecer sesión, endpoint `.well-known/passkey-endpoints`, login "Iniciar sesión con passkey" y gestión en seguridad (registro/lista/eliminación).
- Fuentes vía `laravel-vite-plugin/fonts` (`bunny('Instrument Sans', [400,500,600])`) reemplazando las fuentes locales de `backend/public/fonts`; los `@font-face` se inyectan en build.
- Tests de 2FA y passkeys en Core (`TwoFactorAuthTest`, `PasskeyAuthTest`): staging en login, challenge TOTP, recovery code single-use, middleware por guard y hook `authorizeLoginUsing` (cuenta activa + IP blocklist).

### Changed

- `inertiajs/inertia-laravel` a `^3.0`.
- Nombres de rutas 2FA renombrados: `security.2fa.*` → `security.two-factor.*` (URLs intactas; evita bug del generador con segmentos numéricos).
- `internal.staff.index.redirect` → `internal.staff.root.redirect` (evita self-import en generador).
- Skill `wayfinder-development` extendida con patrones del monorepo (generación `--path=../frontend/src`, `resolveRoute`, pitfalls del generador).

### Fixed

- `resolveRoute()` rompía al recibir URLs relativas (`/internal/...`) desde el backend en la navegación — ahora las URLs ya resueltas pasan intactas y solo se resuelven nombres punteados.
- TOTP en tests: el helper ahora usa `Date::now()` (mismo reloj que el verifier) eliminando flakiness de ventana de 30s en suite.

### Removed

- Ziggy por completo: `ziggy-js` (frontend), `tightenco/ziggy` (backend), `src/ziggy.js`, `global.d.ts` (declaración `route`), `backend/routes/ziggy-debug.php`, componente `ziggy-debug`, `RouteFilterService` + facade, config `ziggy.groups`, prop `'ziggy'` compartida, `config/ziggy.php`, `config/routes.php`, endpoint `/api/routes`.
- `backend/public/fonts` (fuentes ahora vía plugin de Vite).
- 57 call sites `route('...')` reemplazados por imports tipados de Wayfinder.

## [0.3.0-alpha] - 2026-08-25

> Hito del repositorio. Aún no existe un tag ni un release publicado.

### Added

- Paquete `packages/foundry-php-utils`: helpers tipados `Foundry\Helpers` (config/cache normalizados, `userId`, `fileModificationTime`) conectado vía path repository.
- `NavigationComposer` con caché de navegación versionada, extraído tras `NavigationComposerInterface`.
- Clases de estrategia EnvLoader (`backend/bootstrap/EnvLoaders/*`) con resolver (explicit override, testing, docker, production, local).
- Suite Pest unitaria para Core (`Modules/Core/tests/**`: sync de addons, menú, caché de permisos, auditoría de seguridad, caché de view composer).
- `scanFiles` para `helpers.php` y reglas de ignore para Pest en `phpstan.neon`.
- `AbstractDomainUser` (clase base abstracta en Core) con 6 traits compartidos, accessors y defaults, habilitando múltiples tipos de usuario sin duplicar comportamiento.
- `config('core.guards')`: config publicable con guards parametrizables (`staff`, `web`, `sanctum`, `tenant`) + `sync_excludes` para preservar asimetría de sincronización.
- `AuthService::forGuard()` (factory parametrizada por guard) + `AbstractLoginRequest` (base para LoginRequest por guard).
- `AuthUserPresenterInterface` (puerto de serialización en Core) + `StaffUserPresenter` (implementación en Admin) + `TenantUserPresenter` (implementación en Examples).
- `DomainUser`/`DomainUserId`/`DomainUserMapper` (dominio genérico en Core, generalizado desde StaffUser/StaffUserId/StaffUserMapper).
- Sesiones polimórficas (`authenticatable_type` + `authenticatable_id`) reemplazando `staff_user_id` + `user_id` en tabla `sessions`.
- `ExampleTenantUser` (modelo esquelético en Modules/Examples) con guard `tenant`, validando multi-usuario end-to-end sin tocar Core.
- `PermissionRegistryInterface` en Core + 3 implementaciones (Core, Admin, Examples) + comando `permissions:sync-registry` que sincroniza permisos declarados.
- 22 permisos granulares `recurso.accion` (18 staff + 4 tenant) reemplazando los 3 permisos amplios (`access-module-01`, `access-module-02`, `access-admin`).
- `StaffUserPolicy`, `RolePolicy`, `PermissionPolicy` (Laravel Policies en Admin) con autorización por permiso granular.
- CRUD admin de roles: `RolesInterface` + `RoleService` + 4 controllers VerbEntity + `RoleCreateRequest`/`RoleUpdateRequest` + rutas con middleware granular por acción.
- Frontend CRUD UI: páginas de listado/creación/edición de roles, lista de permisos read-only agrupada por módulo, componentes reutilizables (role-form, role-table, role-actions-cell), hook `use-role-form`.
- Unión discriminada `User = StaffUser | TenantUser` en frontend con type guards por `user_type`; escape hatch `[key: string]: unknown` eliminado de `StaffUser`.
- Módulo `Examples` (renombrado desde `Module01`) con rutas y auth coherentes con guard `tenant`.
- `PermissionRegistryAggregator` (servicio inyectable en Core) eliminando service-locator `app()->tagged()` duplicado en controladores.
- Morph map `'staff-user'` registrado en `AppServiceProvider` para estabilizar `model_type` en tablas de Spatie.
- `ModuleConfigInterface` (Core/Contracts, 5 métodos) + `ModuleConfigRegistry` (agregador de tagged services `module-config`, mirror de `PermissionRegistryService`) — contrato Core-controlled para la configuración declarativa de módulos.
- `ModuleConfigValidator` con 6 reglas de integridad (guard válido, `base_permission` declarado en el registry del módulo, `nav_item.route_name` no vacío, sin `$ref:` colgantes, `inertia_view_directory` existe en el frontend, `inertia_view_directory` declarado explícitamente) + comando `modules:validate-config` (flag `--strict`).
- 7 DTOs tipados readonly en Core/Domain: `NavItem`, `NavComponentLink`, `NavComponentGroup`, `ContextualNavMap`, `BreadcrumbItem`, `BreadcrumbMap` (Domain/Menu) + `PanelItem` (Domain/Panel). Validación en constructor, atributo `#[TypeScript]`, factories `::fromConfigArray()`.
- `spatie/laravel-typescript-transformer` (`^3.3`) + `TypeScriptTransformerServiceProvider`: genera `.d.ts` a `frontend/src/types/generated/` desde los DTOs de `Core/src/Domain` (comando `php artisan typescript:transform`). Tipos generados tracked en git; CI verifica drift.
- Configuración declarativa: `config/config.php` de cada módulo como única fuente human-editable; las clases `*ModuleConfig` son adapters delgados (~60-77 líneas) que leen config y delegan a factories DTO. Composición por key-reference (sin strings `$ref:`); prefijo `group:` referencia un `NavComponentGroup`.
- Page-props DTOs con `#[TypeScript]`: `ResolvedNavItem` (href + current), `ResolvedBreadcrumbItem`, `ResolvedPanelItem`, `ModulePageProps`, `GlobalPageProps`, `AuthPageProps`, `SecurityPageProps` — envuelven la salida de los builders en el boundary del composer.
- `DomainUser` extendido con 5 campos (`emailVerifiedAt`, `userType`, `avatar`, `createdAt`, `updatedAt`) + atributo `#[TypeScript]` para generación TS.
- `StaffUserFilter` DTO en `Admin/Domain/Filters/` con `fromRequest()` — elimina doble validación controller+service en `getAllUsers`.
- Helper `modelStringAttribute()` en `foundry-php-utils` para extraer atributos string de modelos Eloquent sin boilerplate PHPStan max.
- Helpers tipados adicionales en `foundry-php-utils`: `configBool`, `arrayString`, `arrayNullableString`, `arrayInt`, `arrayBool`, `stringList`, `assertString`, `assertInstanceOf`.
- Workflow CI `typescript.yml` con drift check: regenera tipos TS + `git diff --exit-code` para detectar drift entre DTOs PHP y tipos generados.

### Changed

- Backend actualizado a Laravel 13.29.0 y Symfony Process 8.1.5, con PHP `^8.4 || ^8.5`.
- Suite de pruebas migrada a Pest 5.1.3 sobre PHPUnit 13.3.1.
- Plugins de Pest 5 integrados: PHPStan 5.2.0, Rector 5.0.4, Agent 5.0.0 y Browser 5.0.1.
- Playwright 1.62.1 añadido en la raíz para las pruebas de navegador de Pest.
- Servicios del backend refactorizados para usar `Foundry\Helpers` en lugar de llamadas inline a `config()`/`cache()`.
- Toolchain frontend actualizado: Vite 8 (rolldown), ESLint 10, react-day-picker 10, TypeScript 6.
- `manualChunks` de `vite.config.ts` convertido a forma de función para rolldown; migración de `ClassNames` del calendario a las claves de react-day-picker v10.
- `printWidth` de Prettier fijado en 100.
- Metadatos de paquete raíz, versión de Bun y adiciones a `.gitignore`.
- `StaffUser` (modelo Eloquent) reubicado de `Modules/Core` a `Modules/Admin/App/Models` (namespace `Modules\Admin\App\Models`).
- Core desacoplado de `StaffUser` concreto: Application y Contracts usan `AuthenticatableUser` (interfaz) o `AbstractDomainUser` (base Eloquent).
- `SessionServiceProvider::getCurrentGuard()` itera guards dinámicamente desde `config('core.guards')` en vez de hardcodear `'staff'`.
- `Controller::requireDomainUser(string $guard)` generaliza `requireStaffUser()`.
- Middleware redirects (`EnsureUserIsActive`, `ValidateSessionIntegrity`) leen de config en vez de hardcodear `'staff'`.
- `HasCrossGuardPermissions::getAvailableGuards()` lee de `config('core.guards')` en vez de array hardcodeado.
- `syncPermissionsBetweenGuards()` movido de método estático del trait a `SyncCrossGuardPermissions::handle()` (application service), lee guards de config.
- `AdminStaffUserService` y `RoleService` delegan invalidación de caché a `PermissionVerifierInterface::clearCache()` (eliminando reimplementaciones con TTL divergente).
- Permisos de Roles normalizados a granular por acción (`roles.view/create/update/delete` reemplazando `roles.manage`).
- `RolePermissionSeeder` reescrito con 22 permisos granulares (big bang — 3 amplios eliminados).
- `ListRolesController` usa `withCount('permissions')` (fix N+1).
- `RoleService::updateRole()` lanza `ValidationException` en intento de renombrar roles protegidos (fix no-op silencioso).
- `StaffUserRequest` reemplaza permiso fantasma `access-admin` con permisos granulares por método.
- `BypassEliminationTest` extendido a 8 archivos (3 Core + 5 Admin).
- `BuildAddonMenu`, `BuildContextualMenu`, `BuildBreadcrumbs` (Application/Menu) + `ModuleOrchestratorService`, `ViewComposerService`, `AddonRegistryService` (Infrastructure) refactorizados para consumir DTOs vía `AddonRegistryInterface::getModuleConfig()` en lugar de arrays crudos con `?? []`.
- `auth.user` serializada con shape plana (sin envoltorio `data`) para coincidir con la declaración TS.
- Estado activo del sidebar resuelto con la prop `current` enviada por el backend (antes comparación de hrefs que nunca matcheaba).
- `icon` guard unificado: allowlist de iconos publicada desde el frontend (antes dos resolvers divergentes — uno dinámico que crasheaba con iconos desconocidos).
- `AuthUserPresenterInterface` usa binding contextual por controller (antes binding global que colisionaba entre Admin y Examples — comportamiento no determinista).
- `NormalizesStaffUserPayload` usa `Hash::make()` (antes `bcrypt()` — inconsistente con seeders/factory).
- `RoleService::updateRole()` lanza `InvalidArgumentException` cuando el payload no trae ni `name` ni `permissions` (antes no-op silencioso que reportaba éxito).
- `StaffUsersLoginInfo` renombrado a `StaffUserLoginInfo` (singular, consistente con `StaffUser`).
- `StaffUserRequest` split en `CreateStaffUserRequest` + `UpdateStaffUserRequest` (antes un FormRequest con ramas create/update).
- `AdminStaffUserService::getAllRoles()` delega a `RolesInterface` (antes duplicaba query + decoración con match including dead `MOD-01`/`MOD-02` cases).
- `AdminDashboardController` SRP: `getRecentActivity()` movido a `AdminStatsService`, `getIconForEvent()` eliminado (concern frontend).
- Rutas de Users migradas a implicit model binding (`{staffUser}` con `Route::model`); controllers reciben `StaffUser` del router (antes `int $id` + `getUserById` manual).
- Rutas de Admin consolidadas de 4 archivos (`web` + `users` + `roles` + `permissions`) a un solo `web.php`.
- `StaffUserLoginInfo` migrada a relación polimórfica (`loginable_type` + `loginable_id`); `StaffUser` usa `morphMany` con orphan-cleanup listener en `deleting`.
- `StaffUserManagerInterface` split: métodos de roles (`getAllRoles`, `getTotalRoles`) removidos (pertenecen a `RolesInterface`); `ALLOWED_SORT_FIELDS` movido al service.
- `StaffUserManagerInterface` operaciones de lectura retornan `DomainUser` (no `StaffUser` Eloquent); `AdminStaffUserService` mapea vía `DomainUserMapper::toDomain()`.
- `profile-layout.tsx` consume `contextualNavItems` desde props del backend (antes 5 items hardcodeados).
- `StaffUserResource` formatea `email_verified_at` como ISO string (antes DateTime que no matcheaba tipo TS).
- Permiso legacy `access-examples` migrado a `examples.dashboard.access` en config + seeder de Examples.

### Fixed

- Crash de ESLint (`contextOrFilename.getFilename is not a function`) fijando la versión de React en `eslint.config.js`.
- Crash de arranque de Inertia v3 en la carga inicial — `backend/config/inertia.php` ahora emite la página inicial como `<script data-page>` (`use_script_element_for_initial_page => true`), requerido por `@inertiajs/react` v3.
- `AuthService::stopImpersonating()` L104 leía clave equivocada `auth.providers.staff_users.model` (no existe) → corregido a `auth.providers.staff.model`.
- `session.php` L216 `same_site` hardcodeado `'lax'` ignorando `SESSION_SAME_SITE` env → corregido a `env('SESSION_SAME_SITE', 'lax')`.
- Bloque `auth.session` muerto en `config/auth.php` con default engañoso `secure=true` → eliminado.
- `STAFF_2FA_REQUIRED` default `true` pero middleware 2FA comentado (config mentía) → default cambiado a `false`.
- `AdminUserSeeder` else-branch no aseguraba `email_verified_at` ni re-asignaba rol en re-runs parciales → hecho idempotente.
- PHPStan saturaba con 8 workers (~4 GiB) → capado a 4 workers en `phpstan.neon`.
- Rector saturaba con 32 workers → capado a 4 workers + 512M en `rector.php`.
- 4 bypasses hardcodeados `hasRole(['ADMIN','DEV'])` en `HasCrossGuardPermissions`, `AddonRegistryService`, `CheckPermission` → reemplazados por permiso `system.bypass` (data auditable, revocable sin deploy).
- `Gate::before` global en `AppServiceProvider` que anulaba toda autorización para ADMIN/DEV → eliminado.
- Permiso fantasma `access-admin` referenciado en 7 sitios pero no declarado en registry ni seeder → eliminado, reemplazado por granulares.
- Service-locator `app()->tagged('permission-registry')` duplicado en 3 controladores → reemplazado por `PermissionRegistryAggregator` inyectable.
- Código muerto en `RouteServiceProvider` (`map`/`mapWebRoutes`/`mapApiRoutes` nunca ejecutados), `routes/api.php` vacío cargado dos veces, binding `staff_user` sin uso → eliminados.
- `AddonRegistryService` leía `nav_components.groups.user_settings_nav` pero Core definía `user_profile_nav` → `globalNavItems` siempre vacío → sidebar "Configuración" nunca renderizaba (parcheado en el frontend con 5 nav items hardcodeados) → fix de raíz + eliminación del parche frontend.
- Ruta quiet huérfana `internal.staff.module01.index` en `LoggingMiddleware` (stale tras el rename Module01→Examples) → reemplazada por `examples`.
- Páginas frontend huérfanas `pages/modules/module01/` (leftover del rename) → eliminadas + regeneración de `ziggy.js`.

### Removed

- Facade `ViewComposer` y `ModuleOrchestrationController` (reemplazados por `NavigationComposer`).
- Guards `throw_unless` redundantes en la migración de permisos.
- 3 permisos amplios (`access-module-01`, `access-module-02`, `access-admin`) reemplazados por 22 granulares.
- 4 bypasses `hasRole(['ADMIN','DEV'])` hardcodeados reemplazados por `system.bypass`.
- `Gate::before` global bypass en `AppServiceProvider`.
- Método estático `syncPermissionsBetweenGuards()` del trait `HasCrossGuardPermissions`.
- `StaffUser` Eloquent model, factory, loginInfo, resource, migraciones y seeders movidos de Core a Admin.
- `StaffUserId`/`StaffUser`(dominio)/`StaffUserMapper` renombrados a `DomainUserId`/`DomainUser`/`DomainUserMapper` (generalizados en Core).
- Columnas `staff_user_id` + `user_id` de tabla `sessions` reemplazadas por `authenticatable_type` + `authenticatable_id`.
- `[key: string]: unknown` escape hatch eliminado de `StaffUser` en frontend.
- Módulo `Module01` renombrado a `Examples`.
- Módulo `Module02` eliminado por completo (front + back) — sin valor de ejemplo; remueve el coupling `AdminStaffUserService`↔`MOD-01/MOD-02` y el permiso legacy `access-module-02`.
- `MenuConfigResolver` (capa de resolución de `$ref:` strings) eliminada — reemplazada por composición directa de DTOs en las clases `*ModuleConfig`.
- `auth.can` (prop Inertia de permisos pre-computados/cacheados, nunca leída por el frontend) eliminada.
- `toArray()` en los 7 DTOs de ModuleConfig eliminado (bridge de compatibilidad temporal durante la migración).
- Parche frontend en `profile-layout.tsx` (5 nav items hardcodeados sobre el bug de `globalNavItems`) eliminado tras el fix de raíz.
- `'table' => 'staff'` inerte en `config/auth.php` provider staff (inconsistente con tabla real `staff_users`) eliminado.
- `routes/api.php` vacío de Admin (0 bytes, nunca cargado) eliminado.
- `routes/{users,roles,permissions}.php` de Admin eliminados (consolidados en `web.php`).
- Match con `'MOD-01'`/`'MOD-02'` en `AdminStaffUserService::getAllRoles()` (código muerto tras eliminación de Module02) eliminado.

## [0.2.0-alpha] - 2026-01-31

### Added

- Core v2 (módulo más maduro del repo) con separación Domain/Application/Infrastructure/Contracts.
- Navegación dinámica y composición de props para Inertia desde Core.
- Soporte de instalación mediante `@foundry-stack/installer`.

### Changed

- Rebrand del proyecto a “Foundry Stack” (antes `laravel-react-modular-stack`).
- Archivos de entorno centralizados en `.envs/` y scripts alineados para cargar env correcto.
- Convenciones de rutas internas para staff (`internal.staff.*`).

### Fixed

- Sincronización de permisos entre guards en flujos de staff.
- Inconsistencias de invalidación de caché relacionadas con navegación/permisos.

## [0.1.0-alpha] - 2025-09-27

### Added

- Base inicial del repositorio (Laravel + React + Inertia) con workspaces.
- Core v1 (legacy): auth/permisos/navegación con servicios grandes y acoplados.
- Módulo Admin y primeros ejemplos de modularidad con nwidart/laravel-modules.
- Soporte de desarrollo local con SQLite.

### Known issues

- Core v1 resultó difícil de mantener/testear en aislamiento por el tamaño de servicios y el acoplamiento (motivación para Core v2).

## Estrategia de Versionado

- Foundry Stack usa SemVer con versiones `0.y.z` (desarrollo inicial): cambios incompatibles pueden ocurrir sin bump mayor.
- El módulo Core usa su versión independiente (actualmente `v2.0.0`) porque evoluciona con un ciclo distinto al “bundle” del repo.
