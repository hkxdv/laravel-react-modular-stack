import TableCardShell from '@/components/data/data-table-card-shell';
import { DataTableColumnHeader } from '@/components/data/data-table-column-header';
import { TanStackDataTable } from '@/components/tanstack/tanstack-data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useFlashToasts } from '@/hooks/use-flash-toasts';
import { useNavigationProgress } from '@/hooks/use-navigation-progress';
import { useServerTable } from '@/hooks/use-server-table';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import AppLayout from '@/layouts/app-layout';
import { ModuleDashboardLayout } from '@/layouts/module-dashboard-layout';
import { UserActionsCell } from '@/pages/modules/admin/components/user/user-actions-cell';
import { create } from '@/routes/internal/staff/admin/users';
import type { StaffUser } from '@/types';
import { extractUserData } from '@/utils/user-data';
import { Head, Link, usePage } from '@inertiajs/react';
import { type ColumnDef, type SortingState } from '@tanstack/react-table';
import { CreditCard, Info, Mail, PlusCircle, User } from 'lucide-react';
import { useMemo } from 'react';
import type { UserListPageProps } from '../interfaces';

/**
 * Normaliza el objeto filters para asegurar que siempre sea un objeto válido.
 * Laravel a veces envía un array vacío cuando no hay filtros.
 */
const normalizeFilters = (
  filters: unknown,
): {
  search?: string;
  role?: string;
  sort_field?: string;
  sort_direction?: string;
} => {
  // Si filters es null, undefined, o un array, retornar objeto vacío
  if (!filters || Array.isArray(filters)) {
    return {};
  }

  // Si es un objeto, asegurar que las propiedades sean strings o undefined
  const f = filters as Record<string, unknown>;
  const result: { search?: string; role?: string; sort_field?: string; sort_direction?: string } =
    {};
  if (typeof f['search'] === 'string') {
    result.search = f['search'];
  }
  if (typeof f['role'] === 'string') {
    result.role = f['role'];
  }
  if (typeof f['sort_field'] === 'string') {
    result.sort_field = f['sort_field'];
  }
  if (typeof f['sort_direction'] === 'string') {
    result.sort_direction = f['sort_direction'];
  }
  return result;
};

export default function UserListPage({
  users: initialUsers,
  filters: rawFilters,
  contextualNavItems,
  mainNavItems,
  moduleNavItems,
  globalNavItems,
  breadcrumbs,
  flash,
  pageTitle,
  description,
}: Readonly<UserListPageProps>) {
  const { auth } = usePage().props;
  const { showError } = useToastNotifications();

  useNavigationProgress({ delayMs: 150 });

  const userData = extractUserData(auth.user);

  useFlashToasts(
    flash
      ? {
          success: flash.success ?? '',
          error: flash.error ?? '',
          info: flash.info ?? '',
          warning: flash.warning ?? '',
        }
      : undefined,
  );

  // Filtros normalizados para evitar errores de tipos
  const filters = normalizeFilters(rawFilters);

  const currentPage: number = initialUsers.meta.current_page;
  const perPage: number = initialUsers.meta.per_page;
  const lastPage: number = initialUsers.meta.last_page;
  const totalUsers: number = initialUsers.meta.total;

  const initialSorting: SortingState = [
    {
      id: filters.sort_field ?? 'created_at',
      desc: filters.sort_direction === 'desc',
    },
  ];

  const {
    pagination,
    sorting,
    setSorting,
    search,
    setSearch,
    isLoading,
    handleServerPaginationChange,
  } = useServerTable({
    routeName: 'internal.staff.admin.users.index',
    initialPageIndex: Math.max(0, currentPage - 1),
    initialPageSize: perPage,
    initialSorting,
    initialSearch: filters.search ?? '',
    partialProps: ['users', 'filters'],
    buildParams: ({ pageIndex, pageSize, sorting, search }) => ({
      page: pageIndex + 1,
      per_page: pageSize,
      search,
      sort_field: sorting[0]?.id,
      sort_direction: sorting[0]?.desc ? 'desc' : 'asc',
    }),
    onError: () => {
      showError('Error al cargar usuarios. Por favor, intenta de nuevo.');
    },
  });

  const columns: ColumnDef<StaffUser>[] = useMemo(
    () => [
      {
        accessorKey: 'name',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nombre" />,
        cell: ({ row }) => {
          const isCurrentUser = row.original.id === auth.user?.id;
          return (
            <div className="flex items-center space-x-2">
              <User className="text-muted-foreground h-4 w-4" />
              <div className="flex items-center gap-2">
                <span className="max-w-30 truncate font-medium sm:max-w-full">
                  {row.original.name}
                </span>
                {isCurrentUser && (
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <Badge variant="outline" className="text-xs" aria-label="Usuario actual">
                        Tú
                      </Badge>
                    </TooltipTrigger>
                    <TooltipContent>Tu usuario actual</TooltipContent>
                  </Tooltip>
                )}
              </div>
            </div>
          );
        },
      },
      {
        accessorKey: 'email',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Email" />,
        cell: ({ row }) => (
          <div className="flex items-center space-x-2">
            <Mail className="text-muted-foreground h-4 w-4" />
            <span className="max-w-45 truncate sm:max-w-62.5 md:max-w-75">
              {row.original.email}
            </span>
          </div>
        ),
      },
      {
        accessorKey: 'roles',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Roles" />,
        cell: ({ row }) => {
          const roles = row.original.roles;
          if (roles.length === 0) {
            return <span className="text-muted-foreground">Sin roles</span>;
          }

          const roleColorMap: Record<string, { textColor: string; tooltip: string }> = {
            ADMIN: {
              textColor: 'text-primary',
              tooltip: 'Acceso completo a todo el sistema',
            },
            DEV: {
              textColor: 'text-primary',
              tooltip: 'Acceso completo a todo el sistema',
            },
          };

          return (
            <div className="flex flex-wrap gap-1.5">
              {roles.map((role) => {
                const roleConfig = roleColorMap[role.name.toUpperCase()] ?? {
                  textColor: 'text-muted-foreground',
                  tooltip: 'Rol estándar del sistema',
                };

                return (
                  <Tooltip key={role.id}>
                    <TooltipTrigger asChild>
                      <Badge variant="outline" className={`${roleConfig.textColor} font-medium`}>
                        <span className="flex items-center gap-1">
                          <CreditCard className="h-3 w-3" />
                          {role.name}
                        </span>
                      </Badge>
                    </TooltipTrigger>
                    <TooltipContent>{roleConfig.tooltip}</TooltipContent>
                  </Tooltip>
                );
              })}
            </div>
          );
        },
        enableSorting: false,
      },
      {
        id: 'actions',
        header: ({ column }) => (
          <DataTableColumnHeader className="pr-4 text-right" column={column} title="" />
        ),
        cell: ({ row }) =>
          auth.user?.id ? (
            <div className="flex justify-end">
              <UserActionsCell row={row} authUserId={auth.user.id} />
            </div>
          ) : null,
        enableSorting: false,
        enableHiding: false,
        meta: { headerAlign: 'right', cellAlign: 'right' },
      },
    ],
    [auth.user],
  );

  const user = auth.user;

  if (!user) {
    return (
      <>
        <Head title="Error de Autenticación" />
        <div className="flex h-screen items-center justify-center">
          <p>Usuario no autenticado. Por favor, inicie sesión de nuevo.</p>
        </div>
      </>
    );
  }

  if (!auth.user) {
    return null;
  }

  return (
    <AppLayout
      user={userData}
      breadcrumbs={breadcrumbs}
      contextualNavItems={contextualNavItems}
      mainNavItems={mainNavItems}
      moduleNavItems={moduleNavItems}
      globalNavItems={globalNavItems}
    >
      <Head title={pageTitle ?? 'Lista de Usuarios'} />
      <ModuleDashboardLayout
        title={pageTitle ?? 'Lista de Usuarios'}
        description={
          description ?? 'Añadir, editar o eliminar cuentas de usuario del sistema interno.'
        }
        userName={userData?.name ?? ''}
        showGreeting={false}
        actions={
          <Link href={create()}>
            <Button variant="default" className="gap-1.5">
              <PlusCircle className="h-4 w-4" />
              Nuevo Usuario
            </Button>
          </Link>
        }
        mainContent={
          <TableCardShell
            title="Todos los usuarios"
            totalBadge={
              <>
                <Badge variant="outline">{totalUsers} total</Badge>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <Info
                      className="text-muted-foreground h-4 w-4 cursor-help"
                      tabIndex={0}
                      aria-label="Información sobre la lista de usuarios"
                    />
                  </TooltipTrigger>
                  <TooltipContent>Lista completa de usuarios con acceso al sistema</TooltipContent>
                </Tooltip>
              </>
            }
            rightHeaderContent={
              <div className="w-full sm:w-auto sm:min-w-75">
                <Input
                  type="search"
                  placeholder="Buscar por nombre o email..."
                  aria-label="Buscar usuarios por nombre o email"
                  value={search}
                  onChange={(e) => {
                    setSearch(e.target.value);
                  }}
                  className="w-full"
                />
              </div>
            }
          >
            <TanStackDataTable<StaffUser, unknown>
              columns={columns}
              data={initialUsers.data}
              searchable={false}
              paginated={true}
              serverPagination={{
                pageIndex: pagination.pageIndex,
                pageSize: pagination.pageSize,
                pageCount: Math.max(1, lastPage),
                onPaginationChange: handleServerPaginationChange,
              }}
              pageSizeOptions={[10, 20, 50, 100]}
              totalItems={totalUsers}
              onSortingChange={(next) => {
                setSorting(next);
              }}
              initialSorting={sorting}
              loading={isLoading}
              skeletonRowCount={10}
              noDataTitle="Sin usuarios"
              noDataMessage="No se encontraron usuarios para mostrar."
            />
          </TableCardShell>
        }
        fullWidth={true}
      />
    </AppLayout>
  );
}
