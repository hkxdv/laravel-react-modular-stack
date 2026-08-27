import type { EnhancedStat } from '@/components/modules/module-enhanced-stats-cards';
import type {
  AuthData,
  BreadcrumbItem,
  ModuleNavItem,
  NavItemDefinition,
  Paginated,
  Role,
  StaffUser,
  User,
  UserRole,
} from '@/types';
import { type PageProps } from '@inertiajs/core';

/**
 * Props base compartidas por todas las páginas de navegación del admin.
 * Contiene los elementos de navegación y breadcrumbs que se repiten en cada interfaz.
 */
export interface BaseNavProps {
  contextualNavItems: NavItemDefinition[];
  mainNavItems: NavItemDefinition[];
  moduleNavItems: NavItemDefinition[];
  globalNavItems: NavItemDefinition[];
  breadcrumbs: BreadcrumbItem[];
}

/**
 * Props para la página principal del panel de administración (`AdminIndex`).
 * Uses type alias to combine PageProps constraint with BaseNavProps nav items,
 * avoiding the interface-extension conflict on contextualNavItems.
 */
export type AdminIndexPageProps = PageProps &
  BaseNavProps & {
    panelItems: ModuleNavItem[];
    stats: EnhancedStat[];
    pageTitle: string;
    description: string;
  };

/**
 * Props para la página de listado de usuarios.
 */
export interface UserListPageProps extends BaseNavProps {
  users: Paginated<StaffUser>;
  filters: {
    search?: string;
    role?: string;
    sort_field?: string;
    sort_direction?: string;
  };
  pageTitle?: string;
  description?: string;
  auth: AuthData;
  flash?: {
    success?: string | null;
    error?: string | null;
    info?: string | null;
    warning?: string | null;
  };
}

/**
 * Props para la página de edición de usuario.
 */
export interface UserEditPageProps extends BaseNavProps {
  user: StaffUser;
  roles: UserRole[];
  auth: { user: User };
  _errors?: Record<string, string>;
  flash?: {
    success?: string | null;
    error?: string | null;
    info?: string | null;
    warning?: string | null;
  };
}

/**
 * Props para la página de creación de usuario.
 */
export interface UserCreatePageProps extends BaseNavProps {
  roles: UserRole[];
  auth: { user: User };
  _errors?: Record<string, string>;
  flash?: {
    success?: string | null;
    error?: string | null;
    info?: string | null;
    warning?: string | null;
  };
}

/**
 * Datos de usuario para formularios (creación/edición) mínimos.
 */
export interface UserFormData {
  /** Identificador numérico único del usuario (presente en edición, ausente en creación). */
  id?: number;
  /** Nombre completo del usuario. */
  name: string;
  /** Dirección de correo electrónico del usuario. */
  email: string;
  /** Contraseña del usuario (requerida en creación, opcional en edición). */
  password: string;
  /** Confirmación de contraseña. */
  password_confirmation: string;
  /** Array de nombres de roles asignados al usuario. */
  roles: string[];
  /** Opción para verificar automáticamente el email del usuario. */
  auto_verify_email: boolean;
}

/**
 * Identificador mínimo de usuario para diálogos/acciones.
 */
export interface UserIdentifier {
  id: number;
  name: string;
  email: string;
}

// Reexportar tipos globales útiles relacionados
export type {
  Role as AdminRole,
  UserListItem as AdminUserListItem,
  UserRole as AdminUserRole,
} from '@/types';

/**
 * Un permiso individual del registro.
 */
export interface PermissionItem {
  name: string;
  description: string;
  guard: string;
}

/**
 * Props para la página de listado de roles.
 */
export interface RoleListPageProps extends BaseNavProps {
  roles: (Role & { permissions_count: number })[];
  totalRoles: number;
  pageTitle?: string;
  description?: string;
  auth: AuthData;
  flash?: {
    success?: string | null;
    error?: string | null;
    info?: string | null;
    warning?: string | null;
  };
}

/**
 * Props para la página de creación de rol.
 */
export interface RoleCreatePageProps extends BaseNavProps {
  permissionsByModule: Record<string, PermissionItem[]>;
  auth: AuthData;
  flash?: {
    success?: string | null;
    error?: string | null;
    info?: string | null;
    warning?: string | null;
  };
}

/**
 * Props para la página de edición de rol.
 */
export interface RoleEditPageProps extends BaseNavProps {
  role: Role;
  rolePermissions: string[];
  permissionsByModule: Record<string, PermissionItem[]>;
  auth: AuthData;
  flash?: {
    success?: string | null;
    error?: string | null;
    info?: string | null;
    warning?: string | null;
  };
}

/**
 * Props para la página de listado de permisos.
 */
export interface PermissionListPageProps extends BaseNavProps {
  permissionsByModule: Record<string, PermissionItem[]>;
  auth: AuthData;
  pageTitle?: string;
  description?: string;
  flash?: {
    success?: string | null;
    error?: string | null;
    info?: string | null;
    warning?: string | null;
  };
}
