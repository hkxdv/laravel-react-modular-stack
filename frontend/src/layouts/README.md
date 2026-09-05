# Layouts

Guía de uso de la jerarquía de layouts del frontend en `src/layouts`.

## Objetivo

- Estandarizar la estructura visual de la aplicación: shell autenticado (barra lateral), páginas de autenticación, dashboards de módulos y secciones de perfil.
- Componer el shell global una sola vez (`AppLayout`) y dejar que cada página elija solo el layout contextual que necesita (`ModuleDashboardLayout`, `ProfileLayout`).

## Estructura del directorio

- `app-layout.tsx`: Layout raíz del área autenticada. Compone `AppSidebarLayout` y expone props extendidas (navegación, breadcrumbs, acciones de cabecera, título/descripción de página).
- `app/`:
  - `app-sidebar-layout.tsx`: Variante del shell con barra lateral (`AppShell variant="sidebar"` + `AppSidebar`). Usada por `AppLayout`.
  - `app-header-layout.tsx`: Variante del shell solo con cabecera (`AppShell` + `AppHeader`), sin barra lateral; para páginas simplificadas.
- `module-dashboard-layout.tsx`: Layout de panel de módulo con encabezado, estadísticas, contenido principal y contenido lateral opcional.
- `auth-layout.tsx`: Layout centrado para páginas de autenticación (login, recuperación de contraseña, 2FA, etc.).
- `profile-layout.tsx`: Layout de secciones de perfil con navegación contextual (Perfil, Contraseña, Seguridad, Notificaciones).

## Componentes y Props Clave

- `AppLayout` (default export)
  - Props: `user`, `children`, `breadcrumbs?`, `mainNavItems?`, `moduleNavItems?`, `contextualNavItems?`, `globalNavItems?`, `headerActions?`, `pageTitle?`, `pageDescription?`, `header?`.
  - Uso: envolver toda página autenticada; resuelve props faltantes desde `usePage().props`.

- `AppSidebarLayout` / `AppHeaderLayout` (default exports)
  - Props (sidebar): `children`, `breadcrumbs`, `user`, `mainNavItems`, `moduleNavItems`, `contextualNavItems`, `globalNavItems`, `headerActions?`.
  - Props (header): `children`, `breadcrumbs?`.
  - Uso: variantes internas del shell; normalmente se consumen vía `AppLayout`, no directamente.

- `ModuleDashboardLayout` (named export)
  - Props: `title`, `description?`, `userName`, `stats?`, `actions?`, `mainContent`, `sideContent?`, `fullWidth?`, `showGreeting?`.
  - Uso: dashboards de módulos (índice, listados con stats). Si `sideContent` existe y `fullWidth=false`, compone rejilla de 2/3 + 1/3.

- `AuthLayout` (default export)
  - Props: `title?`, `description?`.
  - Uso: páginas de autenticación y recuperación de acceso.

- `ProfileLayout` (default export)
  - Props: `children`, `contextualNavItems?`.
  - Uso: secciones de perfil; consume `contextualNavItems` de props de página con fallback a navegación local.

## Flujo típico (página autenticada con dashboard de módulo)

1. La página importa `AppLayout` y lo envuelve todo; el shell resuelve navegación y usuario desde Inertia (`usePage().props`) o las props recibidas.
2. Para el contenido, la página usa `ModuleDashboardLayout` con título, `userName`, `stats` y `mainContent` propios del módulo.
3. Para secciones de perfil, la página usa `ProfileLayout` dentro de `AppLayout`.
4. Las páginas de autenticación (sin shell) usan `AuthLayout`.
5. Si una página autenticada no necesita barra lateral, `AppLayout` puede delegar a la variante `AppHeaderLayout`.
