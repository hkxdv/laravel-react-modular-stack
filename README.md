# laravel-react-modular-stack

Base modular y neutral en Laravel 12 + React/TypeScript con Inertia y Vite. Incluye navegación contextual, filtrado de rutas con Ziggy, soporte opcional para PostgreSQL JSONB (compatible con SQLite en desarrollo), monorepo con linters/formatters, y orquestación de entornos locales y Docker/Nginx.

## Características clave

- Arquitectura modular en backend (Laravel) y frontend (React/TypeScript + Vite).
- Navegación contextual y renderizado dinámico en el frontend.
- Filtrado de rutas con Ziggy para exponer solo lo necesario al cliente.
- Soporte para PostgreSQL con JSONB (compatible con SQLite en desarrollo).
- Monorepo con tooling unificado (Pint, Prettier, ESLint, Vite).

## Requisitos

- Bun 1.2+
- PHP 8.4+ y Composer
- PostgreSQL (opcional)
- Git

## Arquitectura (resumen)

- Modular (backend)
  - backend/Modules/\* separa capacidades en dominios independientes, registrables y testeables.
- Navegación contextual (frontend)
  - Configuración centralizada para construir menús/vistas según contexto.
- Filtrado de rutas (Ziggy)
  - Solo se exponen al cliente las rutas whitelisteadas y necesarias.
- Datos flexibles (JSONB)
  - Uso de columnas json/jsonb con índices GIN en PostgreSQL cuando aplica.

---

> [!NOTE]
> Entornos: **Local (sin Docker)**, **Local (con PostgreSQL)**, **Dockerizado con PostgreSQL y Nginx**.

## Entornos de Desarrollo

### Desarrollo Local (Sin Docker + SQLite)

Este modo es ideal para trabajar rápidamente en el frontend o en tareas del backend que no tienen dependencias complejas de infraestructura. Utiliza **SQLite** como base de datos.

**Configuración inicial:**

> **Crear archivo de entorno local**:
>
> ```bash
> cp .env.local.example .env.local
> ```
>
> **Instalar todas las dependencias** (backend, frontend y paquetes):
>
> ```bash
> bun run i:all
> ```
>
> **Configurar la base de datos y la aplicación**:
>
> ```bash
> bun run migrate:fresh --seed
> ```
>
> **¡Iniciar el entorno de desarrollo!**
>
> ```bash
> bun dev
> ```
>
> Iniciará el servidor de PHP y el servidor de desarrollo de Vite simultáneamente.

---

### Desarrollo Local con PostgreSQL

Este modo permite aprovechar las características avanzadas de PostgreSQL como JSONB, manteniendo la velocidad de desarrollo local.

**Configuración inicial:**

> **Crear archivo de entorno para PostgreSQL local**:
>
> ```bash
> cp .env.pg.local.example .env.pg.local
> ```
>
> **Asegúrate de tener PostgreSQL instalado localmente**
>
> **Configurar la base de datos y la aplicación**:
>
> ```bash
> bun run pg:migrate:fresh --seed
> ```
>
> **¡Iniciar el entorno de desarrollo con PostgreSQL!**
>
> ```bash
> bun pg:dev
> ```
>
> Iniciará el servidor de PHP conectado a PostgreSQL y el servidor de desarrollo de Vite simultáneamente.

---

### Desarrollo con Docker

Este modo proporciona un entorno consistente y aislado, más cercano a producción. Utiliza **PostgreSQL** y **Nginx** en contenedores.

**Configuración inicial:**

> **Crear archivo de entorno para Docker**:
>
> ```bash
> cp .env.docker.example .env.docker
> ```
>
> **Generar y configurar la `APP_KEY`**: Ejecuta el siguiente comando y copia la clave generada en tu archivo `.env.docker`:
>
> ```bash
> bun dk:artisan key:generate --show
> ```
>
> **¡Iniciar el entorno Docker!**
>
> ```bash
> bun run dk:start
> ```
>
> Este comando orquesta todo: levanta los contenedores de Docker (backend, db, nginx) y, al mismo tiempo, inicia el servidor de Vite en tu máquina local.
>
> **Ejecutar migraciones y seeders** (en una terminal separada):
>
> ```bash
> bun dk:artisan migrate:fresh --seed
> ```

---

## Configuración técnica (resumen)

- Monorepo y scripts (package.json raíz)
  - Workspaces: frontend, backend
  - Gestor de scripts: Bun; utilidades: concurrently, dotenv-cli
  - Scripts clave:
    - dev: orquesta backend (php artisan serve en :8080) + queue:listen + Vite
    - ziggy / pg:ziggy: genera frontend/src/ziggy.js desde rutas de Laravel según el entorno
    - Entornos: .env.local (local/SQLite), .env.pg.local (PostgreSQL local), .env.docker (Docker)
    - Docker: dk:start, dk:up, dk:down, dk:logs, dk:artisan, dev:fe:docker

- Frontend (frontend/package.json)
  - Tech stack: React 19, Vite 6, TypeScript 5, Tailwind 4, Inertia React 2, Ziggy JS 2.5, TanStack Query 5
  - Utilidades: laravel-vite-plugin, date-fns, lucide-react, shadcn/ui (ecosistema)

- Vite (frontend/vite.config.ts)
  - envDir: "../" (lee variables del raíz)
  - Server: host dinámico (localhost en local, 0.0.0.0 en prod/Docker), puerto 5173, watch con polling
  - HMR: toma el host de VITE_APP_URL (hostname) cuando APP_RUNNING_IN_CONTAINER=true o en producción
  - Plugins: laravel-vite-plugin (input: src/app.tsx; publicDirectory: ../backend/public; refresh: true), @vitejs/plugin-react, @tailwindcss/vite
  - Alias: @ -> frontend/src

- ESLint (frontend/eslint.config.js)
  - Configuración flat con typescript-eslint (recommended + stylistic)
  - Plugins: react, react-hooks, jsx-a11y, import, unicorn, sonarjs
  - Reglas destacadas: no-explicit-any (error), consistent-type-imports, no-unused-vars con patrones, reglas de calidad (unicorn/sonarjs), integración Prettier

- Backend (backend/composer.json)
  - Framework: Laravel 12 + Inertia Laravel 2, Sanctum, Scout
  - Modularidad: nwidart/laravel-modules
  - Autorización y registro: spatie/laravel-permission, spatie/laravel-activitylog
  - Datos/DTO: spatie/laravel-data
  - Ruteo cliente: tightenco/ziggy
  - Búsqueda: typesense/typesense-php
  - Dev: laravel/telescope, laravel/pint, laravel/pail, phpunit

- Variables de entorno clave
  - VITE_APP_URL: base para HMR (hostname)
  - APP_RUNNING_IN_CONTAINER=true: activa host/HMR para Docker/LAN
  - Servidor backend local: php artisan serve --host=localhost --port=8080 (controlado por scripts)
  - Selección de DB: .env.local (SQLite) vs .env.pg.local (PostgreSQL); scripts pg:\* usan el segundo

- Tips de uso
  - Genera Ziggy después de añadir/modificar rutas: bun run ziggy (o bun run pg:ziggy)
  - Limpieza de cachés/autoload: bun run clear:all
  - Chequeos rápidos: bun run lint, bun run types, composer pint:test

## Créditos y referencia

Este proyecto está basado y toma como punto de partida el React Starter Kit oficial de Laravel. Todo crédito para Laravel y su comunidad.

Documentación oficial: https://laravel.com/docs/12.x/starter-kits
