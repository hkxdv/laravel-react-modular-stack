# Arquitectura (alto nivel)

> **Estado:** Desarrollo activo (alpha)  
> **Última actualización:** 2026-08-24

Este documento describe la arquitectura actual a un nivel alto. La intención es que sirva como “mapa mental” para encontrar dónde vive cada cosa y cuáles son los límites importantes del sistema.

## Alcance

- Describe lo que existe hoy en el repositorio.
- No es una guía de instalación (ver [`INSTALLATION.md`](INSTALLATION.md)).
- No es un roadmap ni una lista de features futuras.
- Evita detalles finos que cambian seguido (config keys, firmas exactas, etc.).

## Vista general

Foundry Stack es un baseline para sistemas internos con:

- Backend Laravel organizado por módulos (`backend/Modules/*`).
- Frontend React (Vite) integrado con Inertia (props desde backend, UI en frontend).
- Entornos gestionados desde `.envs/`.
- Scripts orquestados con Bun (workspaces).

## Code map (dónde está qué)

**Raíz**

- `package.json`: scripts de orquestación (`bun dev`, `bun run be ...`, `bun run fe ...`, `bun run dk:*`).
- `.envs/`: archivos de entorno por escenario (local SQLite, PostgreSQL local, Docker).
- `database/`: migraciones/seeders “a nivel app” y SQLite local (archivo `database.sqlite`).
- `docs/`: documentación “estable” del proyecto (se mantiene mínima).

**Backend (`backend/`)**

- `backend/app/`: glue de Laravel (providers, middleware, requests).
- `backend/config/`: configuración; incluye el wiring de base de datos para SQLite/PG/Docker.
- `backend/Modules/`: módulos del sistema (nwidart/laravel-modules).
  - `Core`: módulo transversal (auth/permisos/navegación/vistas) con separación por capas.
    - `src/Domain/{User,Menu,Panel,Permission,Addon,Stats}/`: entidades y DTOs puros (sin Laravel). `Domain/Menu/` y `Domain/Panel/` albergan los DTOs tipados del contrato de configuración (ver §"Contrato de configuración de módulos").
    - `src/Contracts/`: interfaces de comunicación cross-module (`ModuleConfigInterface`, `PermissionRegistryInterface`, `AddonRegistryInterface`, etc.).
    - `src/Application/{Auth,Permissions,Menu,...}/`: casos de uso (`BuildAddonMenu`, `BuildContextualMenu`, `BuildBreadcrumbs`, ...).
    - `src/Infrastructure/Laravel/{Services,Providers,Facades,Console/Commands}/`: adapters Laravel. Incluye `ModuleConfigRegistry`, `ModuleConfigValidator`, `PermissionRegistryAggregator` y los comandos `modules:validate-config` / `permissions:sync-registry`.
  - `Admin`: módulo de administración. Contiene el CRUD de roles/permisos, Policies de Laravel, `Domain/Filters/StaffUserFilter` (DTO de filtrado), y `PermissionRegistry`. `StaffUserManagerInterface` retorna `DomainUser` (no `StaffUser` Eloquent) en operaciones de lectura.
  - `Examples`: módulo de ejemplo con guard `tenant` y `ExampleTenantUser` (validación multi-usuario).
- `backend/app/Providers/TypeScriptTransformerServiceProvider.php`: configuración de `spatie/laravel-typescript-transformer` (genera `.d.ts` desde los DTOs de Core hacia `frontend/src/types/generated/`).

**Frontend (`frontend/`)**

- `frontend/src/`: UI React.
  - `pages/`: páginas Inertia (incluye pages por módulo, claveadas por `inertia_view_directory`).
  - `layouts/`, `components/`, `lib/`, `hooks/`: piezas compartidas.
  - `types/generated/`: tipos TypeScript generados desde los DTOs PHP de Core (tracked en git — CI verifica drift con `typescript:transform` + `git diff`).
- `frontend/vite.config.ts`: configuración de Vite; toma env desde `.envs/`.

**Paquetes (`packages/`)**

- `packages/foundry-installer/`: CLI `@foundry-stack/installer` para bootstrap del template.
- `packages/foundry-php-utils/`: paquete Composer con helpers tipados `Foundry\Helpers` (normalización de config/cache, `userId`, `fileModificationTime`, `modelStringAttribute`, `assertString`, `assertInstanceOf`, `stringList`); conectado al backend vía path repository.

## Flujos principales (runtime)

**UI (Inertia)**

1. Request a una ruta `internal.*`.
2. Controlador de módulo prepara datos (y delega en servicios transversales cuando aplica).
3. Se arma un payload de props para Inertia. La navegación, breadcrumbs y panel se componen desde los DTOs de `ModuleConfigInterface` (vía `AddonRegistryInterface::getModuleConfig()`), no desde arrays crudos.
4. El frontend renderiza la página React correspondiente, consumiendo las props tipadas (incluye `frontend/src/types/generated/`).

**Autenticación y autorización (multi-guard)**

- El sistema soporta múltiples guards vía `config('core.guards')` (staff, web, sanctum, tenant). Cada guard tiene su propio provider, login_route y redirect_route.
- `AbstractDomainUser` (base abstracta Eloquent en Core) con `AuthenticatableUser` (interfaz) habilitan múltiples tipos de usuario sin tocar Core. `StaffUser` y `ExampleTenantUser` extienden esta base.
- `StaffUser` (Eloquent) vive en `Modules/Admin/App/Models`; `ExampleTenantUser` en `Modules/Examples`.
- Sesiones polimórficas (`authenticatable_type` + `authenticatable_id`) con morph map (`'staff-user'`, `'tenant-user'`).
- `StaffUserLoginInfo` usa relación polimórfica (`loginable_type` + `loginable_id`) — soporta login info para múltiples tipos de usuario.
- `DomainUser` (entidad de dominio genérica en Core) con `#[TypeScript]` — `StaffUserManagerInterface` retorna `DomainUser` en reads (no `StaffUser` Eloquent); `syncRoles` mantiene acoplamiento Eloquent (Spatie requiere `HasRoles`).
- RBAC granular con Spatie: 22 permisos `recurso.accion` declarados vía `PermissionRegistryInterface` + comando `permissions:sync-registry`.
- Policies de Laravel (`StaffUserPolicy`, `RolePolicy`, `PermissionPolicy`) con autorización por permiso granular; los controladores usan `$this->authorize()`.
- Bypass de superuser vía permiso `system.bypass` (data auditable, no hardcoded role check).
- CRUD admin de roles (4 controllers VerbEntity) y lista de permisos read-only (desde `PermissionRegistryAggregator`) en `Modules/Admin`, con middleware granular por acción.
- Unión discriminada `User = StaffUser | TenantUser` en frontend con type guards por `user_type`.

## Contrato de configuración de módulos (ModuleConfig)

Cada módulo expone su configuración (navegación, breadcrumbs, panel, guard, permiso base) implementando `ModuleConfigInterface` en `Core/Contracts`. El patrón replica el de `PermissionRegistryInterface` (tagged services + agregador + comando Artisan).

**Piezas:**

- `ModuleConfigInterface` (5 métodos): `addon(): AddonConfig`, `navItem(): ?NavItem`, `contextualNav(): ContextualNavMap`, `breadcrumbs(): BreadcrumbMap`, `panelItems(): list<PanelItem>`.
- `ModuleConfigRegistry`: recolecta implementaciones tagged `module-config` (registro en el ServiceProvider de cada módulo).
- `ModuleConfigValidator`: 6 reglas de integridad — (1) guard declarado existe en `config('auth.guards')`, (2) `base_permission` declarado en el `PermissionRegistry` del propio módulo (cierra el bug del permiso fantasma), (3) `nav_item.route_name` no vacío cuando `show_in_nav=true`, (4) sin strings `$ref:` colgantes en los DTOs, (5) `inertia_view_directory` existe en `frontend/src/pages/modules/`, (6) `inertia_view_directory` declarado explícitamente. `--strict` promueve warnings a failures.
- `modules:validate-config` (comando Artisan) + validación en boot (solo `local`/`testing`).

**DTOs tipados** (capa Domain de Core, `final readonly`, validación en constructor, atributo `#[TypeScript]`):

| DTO                 | Ubicación       | Rol                                                               |
| ------------------- | --------------- | ----------------------------------------------------------------- |
| `NavItem`           | `Domain/Menu/`  | Item de nav principal (title, routeNameSuffix, icon, permission)  |
| `NavComponentLink`  | `Domain/Menu/`  | NavItem reutilizable + key (para composición)                     |
| `NavComponentGroup` | `Domain/Menu/`  | Grupo con nombre + lista de links                                 |
| `ContextualNavMap`  | `Domain/Menu/`  | Mapa suffix-de-ruta → items de nav contextual                     |
| `BreadcrumbItem`    | `Domain/Menu/`  | title, routeNameSuffix, dynamicTitleProp?                         |
| `BreadcrumbMap`     | `Domain/Menu/`  | Mapa suffix-de-ruta → breadcrumbs                                 |
| `PanelItem`         | `Domain/Panel/` | Item del panel (name, description, routeSuffix, icon, permission) |

**Configuración declarativa:** `config/config.php` de cada módulo es la única fuente human-editable. Las clases `*ModuleConfig` son adapters delgados (~60-77 líneas) que leen `config('slug.*')` y delegan a factories `::fromConfigArray()` en los DTOs. La composición usa key-reference (claves string que referencian bloques de `nav_components`) en vez de strings `$ref:`; el prefijo `group:` referencia un `NavComponentGroup`. `MenuConfigResolver` (capa de resolución de `$ref`) fue eliminada.

**Generación de tipos TypeScript:** `spatie/laravel-typescript-transformer` genera `.d.ts` a `frontend/src/types/generated/` desde los DTOs de `Core/src/Domain` (vía atributo `#[TypeScript]`). Los archivos generados están tracked en git. CI verifica drift: workflow `typescript.yml` regenera tipos y compara con `git diff --exit-code` (si los DTOs PHP cambiaron sin regenerar, CI falla). Comando: `php artisan typescript:transform`.

**Page-props DTOs:** además de los DTOs de configuración, existen DTOs que describen las props Inertia reales que el frontend recibe: `ResolvedNavItem` (con `href` resuelto + `current`), `ResolvedBreadcrumbItem`, `ResolvedPanelItem`, `ModulePageProps`, `GlobalPageProps`, `AuthPageProps`, `SecurityPageProps`. Estos envuelven la salida de `BuildAddonMenu`/`BuildContextualMenu`/`BuildBreadcrumbs` en el boundary del composer.

**Consumidores:** `BuildAddonMenu`, `BuildContextualMenu`, `BuildBreadcrumbs` (Application/Menu) + `ModuleOrchestratorService`, `ViewComposerService`, `AddonRegistryService` (Infrastructure) consumen DTOs vía `AddonRegistryInterface::getModuleConfig()` en lugar de arrays crudos.

**Contrato cross-stack (backend = fuente de verdad):** el backend serializa props Inertia tipadas; el frontend las consume sin re-derivar ni fallbacks. `auth.user` viaja con shape plana (sin envoltorio `data`); `auth.can` (permisos pre-computados) fue eliminado — el frontend filtra con el mismo `permission-checker` que recibe `permissions` desde el backend. El estado activo del sidebar se resuelve con la prop `current` enviada por el backend, no comparando hrefs.

## Invariantes arquitectónicas (reglas que conviene no romper)

Estas reglas importan más que detalles puntuales, porque son las que evitan que el proyecto se vuelva difícil de mantener:

- En `Core`, la capa **Domain** no depende de Laravel (sin Eloquent, sin Facades, sin `Request`, sin `Cache`).
- En `Core`, la capa **Application** orquesta casos de uso y depende de Domain/Contracts, no de detalles de infraestructura.
- Los módulos existen como unidades “runtime” bajo `backend/Modules/*` (registro con nwidart). El sistema asume esa separación.
- La configuración de cada módulo se expone vía `ModuleConfigInterface` (tagged `module-config`); `config/config.php` es la fuente declarativa y las clases `*ModuleConfig` son adapters. No se consumen arrays crudos de config directamente en Core.
- El backend es la fuente de verdad para las props de Inertia; el frontend las consume tipadas sin re-derivar ni fallbacks (incluye `auth.user`, navegación, breadcrumbs).
- La UI no “salta” al backend por rutas ad-hoc: la integración UI se hace por Inertia (y, si aplica, por endpoints API versionados).
- Los comandos de Artisan no se ejecutan “directo” si necesitas env correcto: se usan los scripts del backend (`bun run be artisan ...`, `bun run be pg ...`).

## Límites (boundaries) y puntos de integración

- **Frontend ↔ Backend:** frontera en Inertia (props serializados). Si algo “no cabe” en props, suele ser señal de que hace falta un endpoint dedicado o simplificar el payload.
- **Core ↔ módulos:** Core concentra responsabilidades transversales. Los módulos deberían consumirlas a través de contratos/servicios, no reimplementar su propia versión.
- **Entorno ↔ ejecución:** `.envs/` define configuración; los scripts (`package.json`, `backend/package.json`) son el mecanismo recomendado para inyectar ese env en runtime.

## Preocupaciones transversales (cross-cutting)

- Seguridad: middleware de headers (CSP, etc.), separación de rutas internas (`/internal/*`).
- Performance: caching (permisos/navegación) y invalidación por versión.
- DX: workspaces con Bun; comandos centralizados en `docs/COMMANDS.md`.
- QA backend: Pint + PHPStan + Pest + Rector (script `bun run be qa`).
- QA frontend: ESLint y TypeScript (scripts `bun run fe lint` y `bun run fe types`).

## “Dónde cambio X”

- **Agregar/editar scripts de desarrollo:** `package.json` (raíz) y `backend/package.json`.
- **Ajustar instalación/installer:** `packages/foundry-installer/`.
- **Cambiar autenticación/permisos/navegación:** `backend/Modules/Core/`.
- **Cambiar la config de un módulo (nav, breadcrumbs, panel, guard, permiso base):** `config/config.php` del módulo — la clase `*ModuleConfig` la lee y construye los DTOs. No editar la clase salvo para cambiar la lógica de adaptación.
- **Agregar un permiso nuevo:** declararlo en el `PermissionRegistry` del módulo + sincronizar con `php artisan permissions:sync-registry`.
- **Cambiar los tipos TypeScript de los DTOs:** editar los DTOs en `Core/src/Domain/{Menu,Panel}/` y regenerar con `php artisan typescript:transform`.
- **Cambiar UI/páginas:** `frontend/src/pages/` y `frontend/src/layouts/`.
- **Agregar un módulo nuevo:** `backend/Modules/<ModuleName>/` + registro de nwidart + implementar `ModuleConfigInterface` + tag `module-config` en el ServiceProvider + declarar su `PermissionRegistry` (ver `docs/COMMANDS.md`).
