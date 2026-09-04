import {
  destroy as passkeyDestroy,
  index as passkeyRegisterOptions,
  store as passkeyRegisterStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import AppLayout from '@/layouts/app-layout';
import ProfileLayout from '@/layouts/profile-layout';
import { revoke as sessionsRevoke } from '@/routes/internal/staff/security/sessions';
import {
  recoveryCodes,
  confirm as twoFactorConfirm,
  disable as twoFactorDisable,
  setup as twoFactorSetup,
} from '@/routes/internal/staff/security/two-factor';
import type { BreadcrumbItem, NavItemDefinition } from '@/types';
import { extractUserData } from '@/utils/user-data';
import type { PageProps } from '@inertiajs/core';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { usePasskeyRegister } from '@laravel/passkeys/react';
import { LoaderCircle } from 'lucide-react';
import { useEffect, useState } from 'react';

interface DeviceInfo {
  id: number;
  ip_address: string | null;
  device_type: string | null;
  browser: string | null;
  platform: string | null;
  is_mobile: boolean;
  is_trusted: boolean;
  last_login_at: string | null;
  login_count: number;
}

interface TwoFactorSetupPayload {
  secret: string;
  provisioning_uri: string;
  recovery_codes: string[];
}

interface PasskeyInfo {
  id: number;
  name: string;
  created_at: string | null;
}

interface SecurityPageProps extends PageProps {
  breadcrumbs?: BreadcrumbItem[];
  contextualNavItems?: NavItemDefinition[];
  security: {
    twoFactorRequired: boolean;
    twoFactorEnabled: boolean;
    twoFactorPending: boolean;
    currentSessionId: string | null;
    sessionsCount: number;
    devices: DeviceInfo[];
    passkeys: PasskeyInfo[];
  };
  twoFactorSetup?: TwoFactorSetupPayload | null;
  recoveryCodes?: string[] | null;
}

const startTwoFactorSetup = () => {
  router.post(twoFactorSetup().url, {}, { preserveScroll: true });
};

const disableTwoFactor = () => {
  router.delete(twoFactorDisable().url, { preserveScroll: true });
};

const regenerateRecoveryCodes = () => {
  router.post(recoveryCodes().url, {}, { preserveScroll: true });
};

const revokeOtherSessions = () => {
  router.post(sessionsRevoke().url, {}, { preserveScroll: true });
};

export default function SecurityPage() {
  const { auth, contextualNavItems, flash, security, twoFactorSetup, recoveryCodes, breadcrumbs } =
    usePage<SecurityPageProps>().props;

  const { showSuccess, showError } = useToastNotifications();

  const confirmForm = useForm({
    code: '',
  });

  useEffect(() => {
    if (flash.success) {
      showSuccess(flash.success);
    }
    if (flash.error) {
      showError(flash.error);
    }
  }, [flash, showSuccess, showError]);

  const confirmTwoFactorSetup = () => {
    confirmForm.post(twoFactorConfirm().url, {
      preserveScroll: true,
      onSuccess: () => {
        confirmForm.reset();
      },
    });
  };

  let statusLabel = 'Desactivado';
  if (security.twoFactorEnabled) {
    statusLabel = 'Activado';
  } else if (security.twoFactorPending) {
    statusLabel = 'Pendiente de confirmación';
  }

  return (
    <AppLayout
      user={extractUserData(auth.user)}
      breadcrumbs={breadcrumbs ?? []}
      contextualNavItems={contextualNavItems ?? []}
    >
      <Head title="Seguridad" />

      <ProfileLayout>
        <div className="space-y-8">
          <HeadingSmall
            title="Seguridad"
            description="Gestiona la autenticación en dos pasos y tus sesiones activas"
          />

          <Card className="w-full max-w-3xl">
            <CardHeader>
              <CardTitle>Autenticación en dos pasos (2FA)</CardTitle>
              <CardDescription>
                Estado: <span className="font-medium">{statusLabel}</span>
                {security.twoFactorRequired ? (
                  <span className="text-muted-foreground ml-2">(requerido)</span>
                ) : null}
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              {security.twoFactorEnabled ? (
                <div className="flex flex-wrap items-center gap-3">
                  <Button type="button" variant="destructive" onClick={disableTwoFactor}>
                    Deshabilitar 2FA
                  </Button>
                  <Button type="button" variant="secondary" onClick={regenerateRecoveryCodes}>
                    Regenerar códigos de recuperación
                  </Button>
                </div>
              ) : (
                <div className="flex flex-wrap items-center gap-3">
                  <Button type="button" onClick={startTwoFactorSetup}>
                    Iniciar configuración
                  </Button>
                </div>
              )}

              {twoFactorSetup ? (
                <div className="space-y-6">
                  <Separator />
                  <div className="space-y-2">
                    <div className="text-sm font-medium">Clave secreta</div>
                    <div className="bg-muted rounded-md px-3 py-2 font-mono text-sm">
                      {twoFactorSetup.secret}
                    </div>
                    <div className="text-sm font-medium">URI de aprovisionamiento</div>
                    <div className="bg-muted rounded-md px-3 py-2 font-mono text-sm break-all">
                      {twoFactorSetup.provisioning_uri}
                    </div>
                  </div>

                  <div className="space-y-3">
                    <div className="text-sm font-medium">Confirmar código</div>
                    <div className="grid gap-2 sm:grid-cols-[1fr_auto] sm:items-end">
                      <div className="space-y-1">
                        <Label htmlFor="two_factor_code">Código (6 dígitos)</Label>
                        <Input
                          id="two_factor_code"
                          value={confirmForm.data.code}
                          onChange={(e) => {
                            confirmForm.setData('code', e.target.value);
                          }}
                          placeholder="123456"
                          inputMode="numeric"
                          autoComplete="one-time-code"
                          maxLength={12}
                          className={
                            confirmForm.errors.code
                              ? 'border-red-500 focus-visible:ring-red-500'
                              : 'focus:border-primary focus-visible:ring-primary/30'
                          }
                          disabled={confirmForm.processing}
                        />
                      </div>
                      <Button
                        type="button"
                        onClick={confirmTwoFactorSetup}
                        disabled={confirmForm.processing}
                      >
                        Confirmar 2FA
                      </Button>
                    </div>
                    {confirmForm.errors.code ? (
                      <p className="text-sm text-red-500">{confirmForm.errors.code}</p>
                    ) : null}
                  </div>

                  <div className="space-y-2">
                    <div className="text-sm font-medium">Códigos de recuperación</div>
                    <div className="grid gap-2 sm:grid-cols-2">
                      {twoFactorSetup.recovery_codes.map((code) => (
                        <div key={code} className="bg-muted rounded-md px-3 py-2 font-mono text-sm">
                          {code}
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              ) : null}

              {recoveryCodes && recoveryCodes.length > 0 ? (
                <div className="space-y-2">
                  <Separator />
                  <div className="text-sm font-medium">Códigos de recuperación regenerados</div>
                  <div className="grid gap-2 sm:grid-cols-2">
                    {recoveryCodes.map((code) => (
                      <div key={code} className="bg-muted rounded-md px-3 py-2 font-mono text-sm">
                        {code}
                      </div>
                    ))}
                  </div>
                </div>
              ) : null}
            </CardContent>
          </Card>

          <Card className="w-full max-w-5xl">
            <CardHeader>
              <CardTitle>Passkeys</CardTitle>
              <CardDescription>
                Inicia sesión sin contraseña usando tu dispositivo (Face ID, Touch ID, llave de
                seguridad).
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <PasskeyManager passkeys={security.passkeys} />
            </CardContent>
          </Card>

          <Card className="w-full max-w-5xl">
            <CardHeader>
              <CardTitle>Sesiones activas</CardTitle>
              <CardDescription>
                Total detectadas: <span className="font-medium">{security.sessionsCount}</span>
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex flex-wrap items-center gap-3">
                <Button type="button" variant="secondary" onClick={revokeOtherSessions}>
                  Revocar otras sesiones
                </Button>
              </div>

              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Dispositivo</TableHead>
                    <TableHead>IP</TableHead>
                    <TableHead>Plataforma</TableHead>
                    <TableHead>Último acceso</TableHead>
                    <TableHead className="text-right">Logins</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {security.devices.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={5} className="text-muted-foreground py-6 text-center">
                        No hay dispositivos registrados.
                      </TableCell>
                    </TableRow>
                  ) : (
                    security.devices.map((d) => (
                      <TableRow key={d.id}>
                        <TableCell className="font-medium">
                          {d.device_type ?? 'Dispositivo'} {d.is_mobile ? '(móvil)' : ''}
                          {d.is_trusted ? ' (confiable)' : ''}
                          {d.browser ? (
                            <span className="text-muted-foreground block text-xs">{d.browser}</span>
                          ) : null}
                        </TableCell>
                        <TableCell>{d.ip_address ?? '-'}</TableCell>
                        <TableCell>{d.platform ?? '-'}</TableCell>
                        <TableCell>{d.last_login_at ?? '-'}</TableCell>
                        <TableCell className="text-right">{d.login_count}</TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </div>
      </ProfileLayout>
    </AppLayout>
  );
}

/**
 * Gestión de passkeys: registrar una nueva (WebAuthn) y listar/eliminar las
 * existentes. Tras registrar o eliminar, recarga la página para refrescar la
 * lista (las passkeys llegan del backend en security.edit).
 */
function PasskeyManager({ passkeys }: Readonly<{ passkeys: PasskeyInfo[] }>) {
  const [name, setName] = useState('Mi dispositivo');
  const { register, isLoading, error } = usePasskeyRegister({
    routes: {
      options: passkeyRegisterOptions().url,
      submit: passkeyRegisterStore().url,
    },
    onSuccess: () => {
      router.reload({ only: ['security'] });
    },
  });

  const remove = (passkey: PasskeyInfo) => {
    if (confirm(`¿Eliminar la passkey "${passkey.name}"?`)) {
      router.delete(passkeyDestroy({ id: passkey.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
          router.reload({ only: ['security'] });
        },
      });
    }
  };

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-end gap-3">
        <div className="grid flex-1 gap-1">
          <Label htmlFor="passkey-name">Nombre del dispositivo</Label>
          <Input
            id="passkey-name"
            value={name}
            onChange={(e) => {
              setName(e.target.value);
            }}
            placeholder="Mi dispositivo"
            disabled={isLoading}
          />
        </div>
        <Button
          type="button"
          onClick={() => {
            void register(name.trim() || 'Mi dispositivo');
          }}
          disabled={isLoading}
        >
          {isLoading && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
          {isLoading ? 'Registrando…' : 'Registrar passkey'}
        </Button>
      </div>

      {error && <InputError message={error} />}

      {passkeys.length === 0 ? (
        <p className="text-muted-foreground text-sm">No tienes passkeys registradas.</p>
      ) : (
        <ul className="divide-y">
          {passkeys.map((pk) => (
            <li key={pk.id} className="flex items-center justify-between py-2">
              <div>
                <div className="text-sm font-medium">{pk.name}</div>
                {pk.created_at && (
                  <div className="text-muted-foreground text-xs">Registrada el {pk.created_at}</div>
                )}
              </div>
              <Button
                type="button"
                variant="destructive"
                size="sm"
                onClick={() => {
                  remove(pk);
                }}
              >
                Eliminar
              </Button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
