# Componentes de Módulos

Guía de uso y estilo para los componentes reutilizables del directorio `src/components/modules`.

## Objetivo

- Unificar el uso de componentes de módulo (encabezado, tarjetas de navegación, estados vacíos y estadísticas) con un estilo y tipado consistentes.
- Estandarizar textos, encabezados y TSDoc para una experiencia homogénea entre módulos.

## Estructura del directorio

- `interfaces/`: Tipos e interfaces compartidas (por ejemplo, `ModuleNavItem`, `ModuleNavCardsProps`, etc.).
- `module-header.tsx`: Encabezado del módulo (título, descripción y saludo opcional).
- `module-index-page.tsx`: Contenedor de página que compone encabezado, contenido principal y secciones opcionales.
- `module-index-content.tsx`: Contenido principal del índice del módulo; muestra tarjetas de navegación y estado vacío.
- `module-nav-cards.tsx`: Renderiza tarjetas de navegación de módulos, manejando rutas por nombre, href o rutas directas.
- `module-enhanced-stats-cards.tsx`: Tarjetas de estadísticas con iconos, título, valor y subtítulo.
- `module-empty-state.tsx`: Estado vacío con mensaje e icono.
- `skeletons/`: Esqueletos de carga para secciones específicas (stats, nav cards, módulos restringidos).
- `utils/build-stat.ts`: Helper para construir estadísticas (`EnhancedStat`).

## Componentes y Props Clave

- `ModuleHeader`
  - Props: `title`, `description?`, `userName`, `showGreeting?`.
  - Uso: mostrar encabezado del módulo con saludo opcional.

- `ModuleIndexPage`
  - Props: `user`, `breadcrumbs`, `contextualNavItems?`, `pageTitle`, `description?`, `staffUserName`, `stats?`, `mainContent`, `fullWidth?`.
  - Uso: contenedor principal del dashboard de un módulo.

- `ModuleIndexContent`
  - Props: `isLoading`, `items`, `getIconComponent`, `headerTitle`, `headerDescription?`, `emptyStateMessage?`, `emptyStateIcon?`.
  - Uso: construye la sección “Secciones del Módulo” con tarjetas y estado vacío.

- `ModuleNavCards`
  - Props: `items: ModuleNavItem[]`, `getIconComponent: (icon?: string | LucideIcon | null) => LucideIcon | null`.
  - Uso: renderiza tarjetas de navegación con iconos Lucide y navegación por ruta, href o URL directa.

- `ModuleEnhancedStatsCards`
  - Props: `stats: EnhancedStat[]`.
  - Uso: muestra estadísticas de forma visual y consistente; cada `EnhancedStat` incluye `title`, `value`, `subtitle?` e `icon` (nombre de icono Lucide).

- `ModuleEmptyState`
  - Props: `message?`, `icon?: IconName`, `IconComponent?: LucideIcon`.
  - Uso: render amigable cuando no hay elementos que mostrar.

## Tipado y manejo de iconos

- `IconName`: unión de literales de nombres de iconos Lucide soportados.
- `getLucideIcon(name)`: acepta `string | LucideIcon | null` y retorna `LucideIcon | null`.
- En estructuras de datos (por ejemplo, `ModuleNavItem.icon`), usa `IconName` para tipado estricto.
- En funciones que resuelven iconos (por ejemplo, `getIconComponent`), usa `string | LucideIcon | null` para mayor flexibilidad.

## Flujo típico (panel de módulo)

1. Deriva props desde backend con Inertia (`usePage().props`).
2. Construye estadísticas con helpers (por ejemplo, `buildStat`).
3. Muestra `ModuleEnhancedStatsCards` (o su `Skeleton`) según estado de carga.
4. Muestra `ModuleIndexContent` con tarjetas utilizando `getLucideIcon` como resolvedor de iconos.
5. Compone todo en `ModuleIndexPage` con encabezado, breadcrumbs y contenido principal.
