# Changelog

Todos los cambios notables de Foundry Stack se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

> [!NOTE]
> Este proyecto aún no tiene releases/tags publicados. Este changelog documenta hitos del repositorio y se irá refinando cuando existan releases formales.

## [Unreleased]

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
- `PermissionRegistryInterface` en Core + 4 implementaciones (Core, Admin, Module02, Examples) + comando `permissions:sync-registry` que sincroniza permisos declarados.
- 22 permisos granulares `recurso.accion` (18 staff + 4 tenant) reemplazando los 3 permisos amplios (`access-module-01`, `access-module-02`, `access-admin`).
- `StaffUserPolicy`, `RolePolicy`, `PermissionPolicy` (Laravel Policies en Admin) con autorización por permiso granular.
- CRUD admin de roles: `RolesInterface` + `RoleService` + 4 controllers VerbEntity + `RoleCreateRequest`/`RoleUpdateRequest` + rutas con middleware granular por acción.
- Frontend CRUD UI: páginas de listado/creación/edición de roles, lista de permisos read-only agrupada por módulo, componentes reutilizables (role-form, role-table, role-actions-cell), hook `use-role-form`.
- Unión discriminada `User = StaffUser | TenantUser` en frontend con type guards por `user_type`; escape hatch `[key: string]: unknown` eliminado de `StaffUser`.
- Módulo `Examples` (renombrado desde `Module01`) con rutas y auth coherentes con guard `tenant`.
- `PermissionRegistryAggregator` (servicio inyectable en Core) eliminando service-locator `app()->tagged()` duplicado en controladores.
- Morph map `'staff-user'` registrado en `AppServiceProvider` para estabilizar `model_type` en tablas de Spatie.

### Changed

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
