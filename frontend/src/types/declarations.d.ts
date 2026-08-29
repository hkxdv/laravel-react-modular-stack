import type { AuthData, NavItemDefinition, User } from '@/types';
import type { ErrorBag, Errors } from '@inertiajs/core';
import type { RowData } from '@tanstack/table-core';

/**
 * Extiende las declaraciones de tipo de `@tanstack/table-core`.
 * Esto nos permite añadir una propiedad `meta` personalizada y fuertemente tipada
 * a las instancias de la tabla, para pasar datos y callbacks adicionales.
 */
declare module '@tanstack/table-core' {
  /**
   * Define la estructura personalizada del objeto `meta` de la tabla.
   * Permite inyectar datos y funciones específicas de la aplicación en la tabla.
   */
  interface TableMeta<_TData extends RowData> {
    /** El ID del usuario autenticado, para comprobaciones de permisos en las celdas. */
    authUserId?: number;
    /** Callback para manejar la acción de editar un usuario desde una fila. */
    onEdit?: (user: User) => void;
    /** Callback para manejar la acción de eliminar un usuario desde una fila. */
    onDelete?: (user: User) => void;
  }
}

/**
 * Aumenta el módulo de Inertia.js para agregar tipado a las `PageProps` globales,
 * para asegurar que `usePage().props` tenga los tipos correctos.
 */
declare module '@inertiajs/core' {
  interface PageProps {
    auth: AuthData;
    flash: {
      success?: string;
      error?: string;
      info?: string;
      warning?: string;
      credentials?: { email: string; password: string; fullName: string };
    };
    errors: Errors & ErrorBag;
    quote: { message: string; author: string };
    sidebarOpen: boolean;
    contextualNavItems?: NavItemDefinition[];
    globalNavItems?: NavItemDefinition[];
    passwordChangeRequired?: boolean;
    security?: {
      twoFactorRequired: boolean;
      twoFactorEnabled: boolean;
      twoFactorPending: boolean;
    };
    notificationPreferences?: { email?: boolean; internal?: boolean } | Record<string, unknown>;
  }
}
