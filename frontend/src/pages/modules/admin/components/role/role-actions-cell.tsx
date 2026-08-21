import RowActionsMenu from '@/components/data/row-actions-menu';
import { router } from '@inertiajs/react';
import { Pencil, Trash } from 'lucide-react';
import { useCallback } from 'react';
import { route } from 'ziggy-js';

interface RoleActionsCellProps {
  role: {
    id: number;
    name: string;
  };
}

const PROTECTED_ROLES = new Set(['ADMIN', 'DEV']);

export function RoleActionsCell({ role }: Readonly<RoleActionsCellProps>) {
  const isProtected = PROTECTED_ROLES.has(role.name.toUpperCase());

  const handleEdit = useCallback(() => {
    router.get(route('internal.staff.admin.roles.edit', role.id));
  }, [role.id]);

  const handleDelete = useCallback(() => {
    if (confirm(`¿Estás seguro de que deseas eliminar el rol "${role.name}"?`)) {
      router.delete(route('internal.staff.admin.roles.destroy', role.id), {
        preserveScroll: true,
      });
    }
  }, [role.id, role.name]);

  return (
    <RowActionsMenu
      idToCopy={role.id}
      items={[
        {
          key: 'edit',
          label: isProtected ? 'Editar Rol (Protegido)' : 'Editar Rol',
          icon: <Pencil className="h-4 w-4" />,
          onClick: handleEdit,
        },
        {
          key: 'delete',
          label: isProtected ? 'No se puede eliminar roles protegidos' : 'Eliminar Rol',
          icon: <Trash className="h-4 w-4" />,
          variant: 'destructive',
          onClick: handleDelete,
          disabled: isProtected,
        },
      ]}
    />
  );
}
