import { buildStat } from '@/components/modules/helper/build-stat';
import {
  EnhancedStatsCards,
  type EnhancedStat,
} from '@/components/modules/module-enhanced-stats-cards';
import { ModuleIndexContent } from '@/components/modules/module-index-content';
import { ModuleIndexPage } from '@/components/modules/module-index-page';
import { EnhancedStatsCardsSkeleton } from '@/components/modules/skeletons/module-enhanced-stats-cards-skeleton';
import { useFlashToasts } from '@/hooks/use-flash-toasts';
import { useNavigationProgress } from '@/hooks/use-navigation-progress';
import type { BreadcrumbItem } from '@/types';
import { createBreadcrumbs } from '@/utils/breadcrumbs';
import { getLucideIcon, type IconName } from '@/utils/lucide-icons';
import { extractUserData } from '@/utils/user-data';
import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import type { Module01IndexPageProps } from './interfaces';

/**
 * Componente para el panel principal del Módulo 01.
 */
export default function Module01IndexPanel() {
  const {
    auth,
    panelItems,
    contextualNavItems,
    stats,
    pageTitle,
    description,
    breadcrumbs,
    flash,
  } = usePage<Module01IndexPageProps>().props;

  const isNavigating = useNavigationProgress({ delayMs: 150 });

  const userData = extractUserData(auth.user);

  useFlashToasts(flash);

  // Usar breadcrumbs del backend con fallback
  const computedBreadcrumbs: BreadcrumbItem[] =
    breadcrumbs && breadcrumbs.length > 0
      ? breadcrumbs
      : createBreadcrumbs('internal.module01.index', pageTitle ?? '');

  // Crear estadísticas  para el módulo a partir de los datos del backend
  const moduleStats: EnhancedStat[] = useMemo(
    () =>
      stats
        ? [
            buildStat(
              'Registros hoy',
              stats.totalRegistersToday,
              'Total de registros del día',
              'CalendarCheck' as IconName,
            ),
          ]
        : [],
    [stats],
  );

  // Sección de estadísticas para el dashboard
  const statsSection =
    isNavigating || !stats ? (
      <EnhancedStatsCardsSkeleton />
    ) : (
      <EnhancedStatsCards stats={moduleStats} />
    );

  // Contenido principal para el dashboard
  const mainContent = useMemo(
    () => (
      <ModuleIndexContent
        isLoading={isNavigating || !panelItems}
        items={panelItems ?? []}
        getIconComponent={getLucideIcon}
        headerTitle="Secciones del Módulo"
        headerDescription="Seleccione una opción para continuar."
        emptyStateMessage="No hay secciones disponibles en el módulo de Registro."
        emptyStateIcon="LayoutDashboard"
      />
    ),
    [isNavigating, panelItems],
  );

  return (
    <ModuleIndexPage
      user={userData}
      breadcrumbs={computedBreadcrumbs}
      contextualNavItems={contextualNavItems}
      pageTitle={pageTitle ?? ''}
      description={description}
      staffUserName={userData?.name ?? ''}
      stats={statsSection}
      mainContent={mainContent}
      fullWidth={true}
    />
  );
}
