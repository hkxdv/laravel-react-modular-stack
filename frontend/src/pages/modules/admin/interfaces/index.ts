import type { EnhancedStat } from '@/components/modules/module-enhanced-stats-cards';
import type {
  AuthData,
  BaseModulePageProps,
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
 * Define la estructura de un ítem del panel de administración.
 * Estos ítems se muestran como tarjetas clicables que navegan a diferentes secciones.
 */
export interface PanelItem {
  /** Nombre descriptivo del ítem, se muestra en la tarjeta. */
  name: string;
  /** Descripción breve de la funcionalidad del ítem. */
  description: string;
  /** Nombre de la ruta de Laravel a la que navegará el ítem. */
  route_name: string;
  /** Nombre del ícono de Lucide (opcional) a mostrar en la tarjeta. */
  icon?: string;
  /** Permiso de Spatie requerido (opcional) para ver/acceder al ítem. */
  permission?: string;
}

/**
 * Props para la página principal del panel de administración (`AdminIndex`).
 */
export interface AdminIndexPageProps extends PageProps, BaseModulePageProps<EnhancedStat[]> {
  /** Ítems de navegación de módulo conformes al contrato unificado. */
  panelItems: ModuleNavItem[];
  contextualNavItems: NavItemDefinition[];
  breadcrumbs: BreadcrumbItem[];
  pageTitle: string;
  description: string;
  mainNavItems: NavItemDefinition[];
  moduleNavItems: NavItemDefinition[];
  globalNavItems: NavItemDefinition[];
  stats: EnhancedStat[];
}

/**
 * Props para la página de listado de usuarios.
 */
export interface UserListPageProps {
  users: Paginated<StaffUser>;
  filters: {
    search?: string;
    role?: string;
    sort_field?: string;
    sort_direction?: string;
  };
  contextualNavItems: NavItemDefinition[];
  breadcrumbs: BreadcrumbItem[];
  mainNavItems: NavItemDefinition[];
  moduleNavItems: NavItemDefinition[];
  globalNavItems: NavItemDefinition[];
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
export interface UserEditPageProps {
  user: StaffUser;
  roles: UserRole[];
  auth: { user: User };
  contextualNavItems: NavItemDefinition[];
  mainNavItems: NavItemDefinition[];
  moduleNavItems: NavItemDefinition[];
  globalNavItems: NavItemDefinition[];
  breadcrumbs: BreadcrumbItem[];
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
export interface UserCreatePageProps {
  roles: UserRole[];
  auth: { user: User };
  contextualNavItems: NavItemDefinition[];
  mainNavItems: NavItemDefinition[];
  moduleNavItems: NavItemDefinition[];
  globalNavItems: NavItemDefinition[];
  breadcrumbs: BreadcrumbItem[];
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
  /** Array de roles asignados al usuario. */
  roles?: UserRole[];
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
export interface RoleListPageProps {
  roles: (Role & { permissions_count: number })[];
  totalRoles: number;
  contextualNavItems: NavItemDefinition[];
  breadcrumbs: BreadcrumbItem[];
  mainNavItems: NavItemDefinition[];
  moduleNavItems: NavItemDefinition[];
  globalNavItems: NavItemDefinition[];
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
export interface RoleCreatePageProps {
  permissionsByModule: Record<string, PermissionItem[]>;
  auth: AuthData;
  contextualNavItems: NavItemDefinition[];
  mainNavItems: NavItemDefinition[];
  moduleNavItems: NavItemDefinition[];
  globalNavItems: NavItemDefinition[];
  breadcrumbs: BreadcrumbItem[];
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
export interface RoleEditPageProps {
  role: Role;
  rolePermissions: string[];
  permissionsByModule: Record<string, PermissionItem[]>;
  auth: AuthData;
  contextualNavItems: NavItemDefinition[];
  mainNavItems: NavItemDefinition[];
  moduleNavItems: NavItemDefinition[];
  globalNavItems: NavItemDefinition[];
  breadcrumbs: BreadcrumbItem[];
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
export interface PermissionListPageProps {
  permissionsByModule: Record<string, PermissionItem[]>;
  auth: AuthData;
  contextualNavItems: NavItemDefinition[];
  mainNavItems: NavItemDefinition[];
  moduleNavItems: NavItemDefinition[];
  globalNavItems: NavItemDefinition[];
  breadcrumbs: BreadcrumbItem[];
  pageTitle?: string;
  description?: string;
  flash?: {
    success?: string | null;
    error?: string | null;
    info?: string | null;
    warning?: string | null;
  };
}
