import TableCardShell from '@/components/data/data-table-card-shell';
import { DataTableColumnHeader } from '@/components/data/data-table-column-header';
import { TanStackDataTable } from '@/components/tanstack/tanstack-data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useFlashToasts } from '@/hooks/use-flash-toasts';
import { useNavigationProgress } from '@/hooks/use-navigation-progress';
import AppLayout from '@/layouts/app-layout';
import { ModuleDashboardLayout } from '@/layouts/module-dashboard-layout';
import { RoleActionsCell } from '@/pages/modules/admin/components/role/role-actions-cell';
import { extractUserData } from '@/utils/user-data';
import { Head, Link, usePage } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import { Info, KeyRound, PlusCircle, Shield } from 'lucide-react';
import { useMemo } from 'react';
import { route } from 'ziggy-js';
import type { RoleListPageProps } from '../interfaces';

interface RoleListItem {
  id: number;
  name: string;
  permissions_count: number;
}

export default function RoleListPage({
  roles,
  totalRoles,
  contextualNavItems,
  mainNavItems,
  moduleNavItems,
  globalNavItems,
  breadcrumbs,
  flash,
  pageTitle,
  description,
}: Readonly<RoleListPageProps>) {
  const { auth } = usePage().props;

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

  const columns: ColumnDef<RoleListItem>[] = useMemo(
    () => [
      {
        accessorKey: 'name',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nombre" />,
        cell: ({ row }) => (
          <div className="flex items-center space-x-2">
            <Shield className="text-muted-foreground h-4 w-4" />
            <span className="max-w-30 truncate font-medium sm:max-w-full">{row.original.name}</span>
          </div>
        ),
      },
      {
        accessorKey: 'permissions_count',
        header: ({ column }) => (
          <DataTableColumnHeader column={column} title="Permisos" />
        ),
        cell: ({ row }) => (
          <div className="flex items-center space-x-2">
            <KeyRound className="text-muted-foreground h-4 w-4" />
            <Badge variant="outline">
              {row.original.permissions_count} permisos
            </Badge>
          </div>
        ),
        enableSorting: false,
      },
      {
        id: 'actions',
        header: ({ column }) => (
          <DataTableColumnHeader className="pr-4 text-right" column={column} title="" />
        ),
        cell: ({ row }) => (
          <div className="flex justify-end">
            <RoleActionsCell role={row.original} />
          </div>
        ),
        enableSorting: false,
        enableHiding: false,
        meta: { headerAlign: 'right', cellAlign: 'right' },
      },
    ],
    [],
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

  return (
    <AppLayout
      user={userData}
      breadcrumbs={breadcrumbs}
      contextualNavItems={contextualNavItems}
      mainNavItems={mainNavItems}
      moduleNavItems={moduleNavItems}
      globalNavItems={globalNavItems}
    >
      <Head title={pageTitle ?? 'Lista de Roles'} />
      <ModuleDashboardLayout
        title={pageTitle ?? 'Lista de Roles'}
        description={description ?? 'Crear, editar o eliminar roles del sistema.'}
        userName={userData?.name ?? ''}
        showGreeting={false}
        actions={
          <Link href={route('internal.staff.admin.roles.create')}>
            <Button variant="default" className="gap-1.5">
              <PlusCircle className="h-4 w-4" />
              Nuevo Rol
            </Button>
          </Link>
        }
        mainContent={
          <TableCardShell
            title="Todos los roles"
            totalBadge={
              <>
                <Badge variant="outline">{totalRoles} total</Badge>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <Info
                      className="text-muted-foreground h-4 w-4 cursor-help"
                      tabIndex={0}
                      aria-label="Información sobre la lista de roles"
                    />
                  </TooltipTrigger>
                  <TooltipContent>Lista completa de roles del sistema</TooltipContent>
                </Tooltip>
              </>
            }
          >
            <TanStackDataTable<RoleListItem, unknown>
              columns={columns}
              data={roles}
              searchable={false}
              paginated={false}
            />
          </TableCardShell>
        }
        fullWidth={true}
      />
    </AppLayout>
  );
}
