import type { BreadcrumbItem } from '@/types';
import { resolveRoute } from '@/lib/routing';

/**
 * Crea un arreglo de breadcrumbs estándar para páginas de índice de módulos.
 * @param routeName Nombre de la ruta backend hacia la página actual.
 * @param title Título a mostrar en el breadcrumb actual.
 */
export function createBreadcrumbs(routeName: string, title: string): BreadcrumbItem[] {
  return [
    {
      title,
      href: resolveRoute(routeName),
    },
  ];
}
