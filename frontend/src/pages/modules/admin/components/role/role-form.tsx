import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { AlertCircle } from 'lucide-react';
import type { SyntheticEvent } from 'react';
import type { RoleFormData } from '../../hooks/use-role-form';
import type { PermissionItem } from '../../interfaces';

interface RoleFormProps {
  form: {
    data: RoleFormData;
    setData: (key: keyof RoleFormData | string, value: string | string[]) => void;
    errors: Partial<Record<keyof RoleFormData, string>>;
    processing: boolean;
  };
  onSubmit: (data: RoleFormData) => void;
  submitButtonText: string;
  permissionsByModule: Record<string, PermissionItem[]>;
  isEditing?: boolean;
  handlePermissionChange: (permissionName: string, checked: boolean) => void;
}

const PROTECTED_ROLES = new Set(['ADMIN', 'DEV']);

const RoleForm: React.FC<RoleFormProps> = ({
  form,
  onSubmit,
  submitButtonText,
  permissionsByModule,
  isEditing = false,
  handlePermissionChange,
}) => {
  const handleSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
    event.preventDefault();
    onSubmit(form.data);
  };

  const moduleNames = Object.keys(permissionsByModule);

  return (
    <div className="mx-auto w-full max-w-3xl">
      <form onSubmit={handleSubmit} className="space-y-10">
        {/* Sección de Información del Rol */}
        <div>
          <h3 className="text-foreground mb-6 text-lg font-medium">Información del Rol</h3>
          <div className="grid gap-6 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="name" className="text-muted-foreground text-sm font-normal">
                Nombre del Rol <span className="text-red-500">*</span>
              </Label>
              <Input
                id="name"
                type="text"
                value={form.data.name}
                onChange={(e) => {
                  form.setData('name', e.target.value);
                }}
                placeholder="Introduce el nombre del rol"
                disabled={isEditing && PROTECTED_ROLES.has(form.data.name.toUpperCase())}
                aria-invalid={!!form.errors['name']}
                className={`bg-muted/40 border-input border-b-accent-foreground/50 h-11 border px-4 focus-visible:ring-1 focus-visible:ring-offset-0 ${
                  form.errors['name'] ? 'border-red-500 ring-1 ring-red-500' : ''
                }`}
              />
              {form.errors['name'] && (
                <div className="mt-1 flex items-center gap-1 text-sm text-red-500">
                  <AlertCircle className="h-4 w-4" />
                  <span>{form.errors['name']}</span>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Sección de Permisos */}
        <div>
          <h3 className="text-foreground mb-6 text-lg font-medium">Permisos</h3>
          <p className="text-muted-foreground mb-4 text-sm">
            Selecciona los permisos que deseas asignar a este rol
          </p>

          {moduleNames.length === 0 && (
            <div className="mb-4 rounded-md border border-yellow-300 bg-yellow-50 p-3 text-sm text-yellow-800 dark:border-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
              No se han cargado permisos. Verifica la conexión con el backend.
            </div>
          )}

          <div className="space-y-6">
            {moduleNames.map((moduleName) => (
              <div key={moduleName}>
                <h4 className="text-foreground mb-3 text-sm font-medium">{moduleName}</h4>
                <div className="grid gap-2 md:grid-cols-2 lg:grid-cols-3">
                  {(permissionsByModule[moduleName] ?? []).map((permission) => {
                    const isChecked = form.data.permissions.includes(permission.name);

                    return (
                      <div
                        key={permission.name}
                        role="button"
                        tabIndex={0}
                        aria-pressed={isChecked}
                        onClick={() => {
                          handlePermissionChange(permission.name, !isChecked);
                        }}
                        onKeyDown={(e) => {
                          if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            handlePermissionChange(permission.name, !isChecked);
                          }
                        }}
                        className={`flex cursor-pointer items-center gap-2 rounded-md border p-2 transition-all ${
                          isChecked
                            ? 'border-gray-500 bg-gray-50 dark:border-gray-500 dark:bg-gray-950/30'
                            : 'border-border hover:bg-muted/50 bg-transparent'
                        }`}
                      >
                        <Checkbox
                          checked={isChecked}
                          onCheckedChange={(checked) => {
                            handlePermissionChange(permission.name, checked === true);
                          }}
                        />
                        <div className="flex-1">
                          <span className="text-sm font-medium">{permission.name}</span>
                          <p className="text-muted-foreground text-xs">{permission.description}</p>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            ))}
          </div>

          {form.errors['permissions'] && (
            <div className="mt-2 flex items-center gap-1 text-sm text-red-500">
              <AlertCircle className="h-4 w-4" />
              <span>{form.errors['permissions']}</span>
            </div>
          )}
        </div>

        <div className="border-border flex flex-col justify-between gap-4 border-t pt-6 sm:flex-row">
          <Button
            type="submit"
            disabled={form.processing}
            className="h-11 rounded-md bg-black font-medium text-white hover:bg-black/90 dark:bg-white dark:text-black dark:hover:bg-white/90"
          >
            {form.processing ? 'Procesando...' : submitButtonText}
          </Button>
        </div>
      </form>
    </div>
  );
};

export default RoleForm;
