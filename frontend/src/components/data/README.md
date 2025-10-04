# Flujo consolidado de tablas (useServerTable + TanStackDataTable + TableCardShell)

Flujo recomendado para construir tablas en el frontend, los componentes finales a utilizar y los patrones que quedan deprecados.

## Objetivo

Unificar la experiencia de usuario y el código para todas las tablas: encabezado consistente, búsqueda, paginación/ordenamiento en servidor y un contenedor visual estándar.

## Componentes finales

- useServerTable (hook)
  - Responsabilidad: centralizar estado/control de búsqueda, ordenamiento y paginación cuando los datos vienen del servidor.
  - Opciones principales: routeName, initialPageIndex, initialPageSize, initialSorting, initialSearch, buildParams(params) => query, onError.
  - Retorno: { pagination, sorting, setSorting, search, setSearch, isLoading, handleServerPaginationChange }.

- TanStackDataTable (componente)
  - Responsabilidad: render de la tabla con TanStack en “modo manual” para trabajar con datos paginados del servidor.
  - Props relevantes: columns, data, paginated, serverPagination { pageIndex, pageSize, pageCount, onPaginationChange }, onSortingChange, initialSorting, loading, skeletonRowCount, totalItems, pageSizeOptions, searchable, noDataTitle, noDataMessage.

- TableCardShell (componente)
  - Responsabilidad: contenedor visual estandarizado (card) con encabezado, título, badge de totales y área derecha para acciones (por ejemplo, buscador).
  - Slots/props: title, totalBadge (opcional), rightHeaderContent (opcional) y children (la tabla).

## Flujo de datos

1. La página recibe filtros y paginación inicial desde el backend. 2) Se prepara initialSorting/search según filtros. 3) useServerTable gestiona la navegación y sincroniza params. 4) TanStackDataTable se renderiza en modo manual, usando serverPagination y onSortingChange. 5) TableCardShell envuelve la tabla y provee un encabezado consistente.

## Patrón recomendado (paso a paso)

1. Deriva currentPage, perPage, lastPage y totalItems desde la respuesta del backend.
2. Inicializa useServerTable con initialPageIndex, initialPageSize, initialSorting y initialSearch.
3. Define columns (incluye acciones si aplica).
4. Usa TableCardShell con title, totalBadge y rightHeaderContent (por ejemplo, Input de búsqueda enlazado a search/setSearch).
5. Renderiza TanStackDataTable con:
   - data y columns
   - serverPagination { pageIndex, pageSize, pageCount, onPaginationChange: handleServerPaginationChange }
   - onSortingChange y initialSorting (del hook)
   - loading, totalItems y pageSizeOptions

## Migración desde implementaciones anteriores

- Reemplazar Card/CardHeader/CardContent alrededor de la tabla por TableCardShell.
- Unificar el estado de búsqueda en el hook (search/setSearch) y evitar estados duplicados en la página.
- Mantener el ordenamiento inicial en sincronía con filtros del backend (initialSorting).
- Si el backend devuelve meta.\*, úsalo para page/size/total; si no, usa el formato estándar de Laravel (propiedades a nivel raíz).

## Componentes/patrones deprecados

- Envolver tablas con Card/CardHeader/CardContent manualmente en cada página.
- Implementar paginación client-side cuando la fuente de datos es el servidor.
- Definir sorting inicial fijo que no refleje los filtros del servidor.

## Ejemplo mínimo (esquema)

```tsx
<TableCardShell
  title="Listado"
  totalBadge={<Badge variant="outline">{totalItems} total</Badge>}
  rightHeaderContent={
    <Input placeholder="Buscar..." value={search} onChange={(e) => setSearch(e.target.value)} />
  }
>
  <TanStackDataTable
    columns={columns}
    data={rows}
    paginated
    serverPagination={{
      pageIndex: pagination.pageIndex,
      pageSize: pagination.pageSize,
      pageCount: lastPage,
      onPaginationChange: handleServerPaginationChange,
    }}
    onSortingChange={setSorting}
    initialSorting={sorting}
    loading={isLoading}
    totalItems={totalItems}
  />
</TableCardShell>
```

## Notas

- Usa noDataTitle/noDataMessage para vacíos amigables.
- Ajusta skeletonRowCount según la densidad de datos esperada.
- Mantén coherencia de títulos/subtítulos con PageHeader en cada página.
