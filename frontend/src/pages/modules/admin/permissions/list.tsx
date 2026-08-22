import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useFlashToasts } from '@/hooks/use-flash-toasts';
import { useNavigationProgress } from '@/hooks/use-navigation-progress';
import AppLayout from '@/layouts/app-layout';
import { ModuleDashboardLayout } from '@/layouts/module-dashboard-layout';
import { PermissionGroupList } from '@/pages/modules/admin/components/permission/permission-group-list';
import { extractUserData } from '@/utils/user-data';
import { Head, usePage } from '@inertiajs/react';
import { Info, KeyRound } from 'lucide-react';
import type { PermissionListPageProps } from '../interfaces';

export default function PermissionListPage({
  permissionsByModule,
  contextualNavItems,
  mainNavItems,
  moduleNavItems,
  globalNavItems,
  breadcrumbs,
  flash,
  pageTitle,
  description,
}: Readonly<PermissionListPageProps>) {
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

  const user = auth.user;

  if (!user) {
    return (
      <>
        <Head title="Error de Autenticacion" />
        <div className="flex h-screen items-center justify-center">
          <p>Usuario no autenticado. Por favor, inicie sesion de nuevo.</p>
        </div>
      </>
    );
  }

  const totalPermissions = Object.values(permissionsByModule).reduce(
    (sum, perms) => sum + perms.length,
    0,
  );

  return (
    <AppLayout
      user={userData}
      breadcrumbs={breadcrumbs}
      contextualNavItems={contextualNavItems}
      mainNavItems={mainNavItems}
      moduleNavItems={moduleNavItems}
      globalNavItems={globalNavItems}
    >
      <Head title={pageTitle ?? 'Permisos del Sistema'} />
      <ModuleDashboardLayout
        title={pageTitle ?? 'Permisos del Sistema'}
        description={description ?? 'Consultar permisos granulares por modulo.'}
        userName={userData?.name ?? ''}
        showGreeting={false}
        mainContent={
          <div className="space-y-6">
            <div className="flex items-center gap-2">
              <KeyRound className="text-muted-foreground h-4 w-4" />
              <span className="text-muted-foreground text-sm">
                {totalPermissions} permisos en {Object.keys(permissionsByModule).length} modulos
              </span>
              <Tooltip>
                <TooltipTrigger asChild>
                  <Info
                    className="text-muted-foreground h-4 w-4 cursor-help"
                    tabIndex={0}
                    aria-label="Informacion sobre los permisos"
                  />
                </TooltipTrigger>
                <TooltipContent>
                  Los permisos se organizan por modulo. Cada modulo declara sus permisos
                  granulares.
                </TooltipContent>
              </Tooltip>
            </div>
            <PermissionGroupList permissionsByModule={permissionsByModule} />
          </div>
        }
        fullWidth={true}
      />
    </AppLayout>
  );
}
