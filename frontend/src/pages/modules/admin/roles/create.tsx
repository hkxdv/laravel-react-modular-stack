import { useFlashToasts } from '@/hooks/use-flash-toasts';
import { useNavigationProgress } from '@/hooks/use-navigation-progress';
import AppLayout from '@/layouts/app-layout';
import { extractUserData } from '@/utils/user-data';
import { Head, usePage } from '@inertiajs/react';
import RoleForm from '../components/role/role-form';
import { useRoleForm } from '../hooks/use-role-form';
import type { RoleCreatePageProps } from '../interfaces';

export default function RoleCreatePage({
  permissionsByModule,
  auth,
  contextualNavItems,
  mainNavItems,
  moduleNavItems,
  globalNavItems,
  breadcrumbs,
  flash,
}: Readonly<RoleCreatePageProps>) {
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
      <Head title="Crear Nuevo Rol" />
      <div className="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <div className="mb-8">
          <h1 className="text-foreground text-2xl font-semibold tracking-tight">Crear Nuevo Rol</h1>
          <p className="text-muted-foreground mt-1 text-sm">
            Completa el formulario para crear un nuevo rol con sus permisos
          </p>
        </div>

        <div className="mt-12">
          <RoleCreateManager permissionsByModule={permissionsByModule} />
        </div>
      </div>
    </AppLayout>
  );
}

interface RoleCreateManagerProps {
  permissionsByModule: Record<string, { name: string; description: string; guard: string }[]>;
}

const RoleCreateManager: React.FC<RoleCreateManagerProps> = ({ permissionsByModule }) => {
  const { errors } = usePage().props as { errors: Record<string, string> };
  const { form, handleSubmit, handlePermissionChange } = useRoleForm();

  // Asignar errores de validación del backend al formulario
  if (Object.keys(errors).length > 0) {
    form.errors = errors;
  }

  return (
    <RoleForm
      form={form}
      onSubmit={handleSubmit}
      submitButtonText="Crear Rol"
      permissionsByModule={permissionsByModule}
      isEditing={false}
      handlePermissionChange={handlePermissionChange}
    />
  );
};
