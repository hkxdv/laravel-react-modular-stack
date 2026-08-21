import { useFlashToasts } from '@/hooks/use-flash-toasts';
import { useNavigationProgress } from '@/hooks/use-navigation-progress';
import AppLayout from '@/layouts/app-layout';
import type { Role } from '@/types';
import { extractUserData } from '@/utils/user-data';
import { Head, usePage } from '@inertiajs/react';
import RoleForm from '../components/role/role-form';
import { useRoleForm } from '../hooks/use-role-form';
import type { RoleEditPageProps } from '../interfaces';

export default function RoleEditPage({
  role,
  rolePermissions,
  permissionsByModule,
  auth,
  contextualNavItems,
  mainNavItems,
  moduleNavItems,
  globalNavItems,
  breadcrumbs,
  flash,
}: Readonly<RoleEditPageProps>) {
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

  return (
    <AppLayout
      breadcrumbs={breadcrumbs}
      user={userData}
      contextualNavItems={contextualNavItems}
      mainNavItems={mainNavItems}
      moduleNavItems={moduleNavItems}
      globalNavItems={globalNavItems}
    >
      <Head title={`Editar Rol: ${role.name}`} />
      <div className="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <div className="mb-8">
          <h1 className="text-foreground text-2xl font-semibold tracking-tight">
            Editar Rol: {role.name}
          </h1>
          <p className="text-muted-foreground mt-1 text-sm">
            Actualiza la información del rol y sus permisos en el sistema
          </p>
        </div>

        <div className="mt-12">
          <RoleEditManager
            role={role}
            rolePermissions={rolePermissions}
            permissionsByModule={permissionsByModule}
          />
        </div>
      </div>
    </AppLayout>
  );
}

interface RoleEditManagerProps {
  role: Role;
  rolePermissions: string[];
  permissionsByModule: Record<string, { name: string; description: string; guard: string }[]>;
}

const RoleEditManager: React.FC<RoleEditManagerProps> = ({
  role,
  rolePermissions,
  permissionsByModule,
}) => {
  const { errors } = usePage().props as { errors: Record<string, string> };
  const { form, handleSubmit, handlePermissionChange } = useRoleForm(role.id, rolePermissions);

  // Pre-populate form with role name
  if (form.data.name === '' && role.name) {
    form.setData('name', role.name);
  }

  // Asignar errores de validación del backend al formulario
  if (Object.keys(errors).length > 0) {
    form.errors = errors;
  }

  return (
    <RoleForm
      form={form}
      onSubmit={handleSubmit}
      submitButtonText="Actualizar Rol"
      permissionsByModule={permissionsByModule}
      isEditing={true}
      handlePermissionChange={handlePermissionChange}
    />
  );
};
