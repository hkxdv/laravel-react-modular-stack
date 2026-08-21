import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { KeyRound } from 'lucide-react';
import type { PermissionItem } from '../../interfaces';

interface PermissionGroupListProps {
  permissionsByModule: Record<string, PermissionItem[]>;
}

export function PermissionGroupList({ permissionsByModule }: Readonly<PermissionGroupListProps>) {
  const modules = Object.entries(permissionsByModule);

  if (modules.length === 0) {
    return (
      <Card>
        <CardContent className="py-8 text-center">
          <p className="text-muted-foreground">No hay permisos registrados.</p>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className="space-y-4">
      {modules.map(([moduleName, permissions]) => (
        <Card key={moduleName}>
          <CardHeader className="pb-3">
            <CardTitle className="flex items-center gap-2 text-base">
              <KeyRound className="h-4 w-4" />
              {moduleName}
              <Badge variant="secondary" className="ml-auto">
                {permissions.length}
              </Badge>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
              {permissions.map((perm) => (
                <div
                  key={perm.name}
                  className="border-border flex flex-col rounded-md border p-3"
                >
                  <span className="text-sm font-medium">{perm.name}</span>
                  <span className="text-muted-foreground mt-0.5 text-xs">{perm.description}</span>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}
