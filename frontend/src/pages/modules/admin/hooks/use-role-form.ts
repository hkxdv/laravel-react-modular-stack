import { destroy, store, update } from '@/routes/internal/staff/admin/roles';
import { router, useForm } from '@inertiajs/react';

/**
 * Datos del formulario de rol
 */
export interface RoleFormData {
  name: string;
  permissions: string[];
  [key: string]: string | string[];
}

/**
 * Errores del formulario de rol
 */
export interface RoleFormErrors {
  name?: string;
  permissions?: string;
}

/**
 * Maneja la eliminación de un rol (función pura fuera del hook para cumplir consistent-function-scoping).
 */
const deleteRole = (roleId: number, roleName: string): void => {
  if (confirm(`¿Estás seguro de que deseas eliminar el rol "${roleName}"?`)) {
    router.delete(destroy(roleId).url);
  }
};

/**
 * Hook personalizado para manejar la lógica del formulario de rol
 * @param initialRoleId ID del rol inicial (para edición)
 * @param initialPermissions Permisos iniciales del rol (para edición)
 * @returns Objeto con el formulario y funciones de manejo
 */
export const useRoleForm = (initialRoleId?: number, initialPermissions?: string[]) => {
  const form = useForm<RoleFormData>({
    name: '',
    permissions: initialPermissions ?? [],
  });

  /**
   * Maneja el envío del formulario para crear o editar un rol.
   */
  const handleSubmit = (data: RoleFormData) => {
    const isEditing = !!initialRoleId;
    const method = isEditing ? 'put' : 'post';
    const url = isEditing ? update(initialRoleId).url : store().url;

    router[method](url, data, {
      onSuccess: () => {
        if (method === 'post') {
          form.reset();
        }
      },
    });
  };

  /**
   * Maneja el cambio de un permiso en el formulario
   */
  const handlePermissionChange = (permissionName: string, checked: boolean) => {
    const updatedPermissions = checked
      ? [...form.data.permissions, permissionName]
      : form.data.permissions.filter((name) => name !== permissionName);

    form.setData('permissions', updatedPermissions);
  };

  return {
    form,
    handleSubmit,
    handleDelete: deleteRole,
    handlePermissionChange,
  };
};
