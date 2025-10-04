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
import type { StaffUser } from '@/types';
import { createBreadcrumbs } from '@/utils/breadcrumbs';
import { extractUserData } from '@/utils/user-data';
import { type PageProps } from '@inertiajs/core';
import { Head, Link, usePage } from '@inertiajs/react';
import { type ColumnDef, type SortingState } from '@tanstack/react-table';
import { CreditCard, Info, Mail, PlusCircle, User } from 'lucide-react';
import { useMemo } from 'react';
import { route } from 'ziggy-js';
import type { UserListPageProps } from '../interfaces';

export default function UserListPage({
  users: initialUsers,
  filters,
  contextualNavItems,
  breadcrumbs,
  flash,
}: Readonly<UserListPageProps>) {
  const { auth } = usePage<PageProps>().props;
  const users = initialUsers ?? { data: [], meta: {}, links: {} };
  const { showError } = useToastNotifications();

  useNavigationProgress({ delayMs: 150 });

  const userData = extractUserData(auth.user);

  useFlashToasts(
    flash
      ? {
          success: flash.success ?? undefined,
          error: flash.error ?? undefined,
          info: flash.info ?? undefined,
          warning: flash.warning ?? undefined,
        }
      : undefined,
  );

  // Paginación/contadores desde respuesta del servidor (formato Laravel meta.*)
  const currentPage: number = users.meta?.current_page ?? 1;
  const perPage: number = users.meta?.per_page ?? 10;
  const lastPage: number = users.meta?.last_page ?? 1;
  const totalUsers: number = users.meta?.total ?? users.data.length;

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
    routeName: 'internal.admin.users.index',
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
    onError: () => showError('Error al cargar usuarios. Por favor, intenta de nuevo.'),
  });

  const columns: ColumnDef<StaffUser>[] = useMemo(
    () => [
      {
        accessorKey: 'name',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nombre" />,
        cell: ({ row }) => {
          const isCurrentUser = auth.user && row.original.id === auth.user.data.id;
          return (
            <div className="flex items-center space-x-2">
              <User className="text-muted-foreground h-4 w-4" />
              <div className="flex items-center gap-2">
                <span className="max-w-[120px] truncate font-medium sm:max-w-full">
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
            <span className="max-w-[180px] truncate sm:max-w-[250px] md:max-w-[300px]">
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
          if (!roles || roles.length === 0) {
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
                const roleConfig = roleColorMap[role.name.toUpperCase()] || {
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
          auth.user?.data.id ? (
            <div className="flex justify-end">
              <UserActionsCell row={row} authUserId={auth.user.data.id} />
            </div>
          ) : null,
        enableSorting: false,
        enableHiding: false,
        meta: { headerAlign: 'right', cellAlign: 'right' },
      },
    ],
    [auth.user],
  );

  const user = auth?.user;

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

  // Fallback de breadcrumbs si no vienen desde el servidor
  const computedBreadcrumbs =
    breadcrumbs && breadcrumbs.length > 0
      ? breadcrumbs.map((b) => ({
          ...b,
          href:
            typeof b.href === 'string' && !b.href.startsWith('http') && b.href !== '#'
              ? route(b.href)
              : b.href,
        }))
      : createBreadcrumbs('internal.admin.users.index', 'Lista de Usuarios');

  return (
    <AppLayout
      user={userData}
      contextualNavItems={contextualNavItems}
      breadcrumbs={computedBreadcrumbs}
    >
      <Head title="Lista de Usuarios" />
      <ModuleDashboardLayout
        title="Lista de Usuarios"
        description="Añadir, editar o eliminar cuentas de usuario del sistema interno."
        userName={userData?.name ?? ''}
        showGreeting={false}
        actions={
          <Link href={route('internal.admin.users.create')}>
            <Button variant="default" className="gap-1.5">
              <PlusCircle className="h-4 w-4" />
              Nuevo Usuario
            </Button>
          </Link>
        }
        mainContent={
          <div className="w-full px-6 py-6">
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
                    <TooltipContent>
                      Lista completa de usuarios con acceso al sistema
                    </TooltipContent>
                  </Tooltip>
                </>
              }
              rightHeaderContent={
                <div className="w-full sm:w-auto sm:min-w-[300px]">
                  <Input
                    type="search"
                    placeholder="Buscar por nombre o email..."
                    aria-label="Buscar usuarios por nombre o email"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="w-full"
                  />
                </div>
              }
            >
              <TanStackDataTable<StaffUser, unknown>
                columns={columns}
                data={Array.isArray(users.data) ? users.data : []}
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
                onSortingChange={(next) => setSorting(next)}
                initialSorting={sorting}
                loading={isLoading}
                skeletonRowCount={10}
                noDataTitle="Sin usuarios"
                noDataMessage="No se encontraron usuarios para mostrar."
              />
            </TableCardShell>
          </div>
        }
        fullWidth={true}
      />
    </AppLayout>
  );
}
