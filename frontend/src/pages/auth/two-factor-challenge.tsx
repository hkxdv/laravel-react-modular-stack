import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { verify } from '@/routes/security/two-factor-challenge';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import React, { type SubmitEventHandler } from 'react';

import { useToastNotifications } from '@/hooks/use-toast-notifications';

/**
 * Página del challenge de 2FA durante el login.
 * El backend redirige aquí (Inertia) tras credenciales válidas cuando el
 * usuario tiene 2FA confirmado; esta página envía el código TOTP o recovery
 * code a `security.two-factor-challenge.verify`.
 */
export default function TwoFactorChallenge() {
  const form = useForm<Required<{ code: string }>>({
    code: '',
  });
  const { showError } = useToastNotifications();

  const submit: SubmitEventHandler = (e) => {
    e.preventDefault();

    form.post(verify().url, {
      preserveScroll: true,
      onError: () => {
        showError('Código inválido', {
          description: form.errors.code ?? 'Verifica el código e inténtalo de nuevo.',
        });
      },
    });
  };

  const errorMessage = React.useMemo(() => form.errors.code ?? '', [form.errors.code]);

  return (
    <AuthLayout
      title="Verificación en dos pasos"
      description="Ingresa el código de 6 dígitos de tu aplicación autenticadora (o un código de recuperación) para completar el inicio de sesión."
    >
      <Head title="Verificación en dos pasos" />

      {errorMessage && (
        <div
          className="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-center text-sm text-red-700"
          role="alert"
          aria-live="polite"
        >
          {errorMessage}
        </div>
      )}

      <form onSubmit={submit}>
        <div className="space-y-6">
          <div className="grid gap-2">
            <Label htmlFor="code">Código de verificación</Label>
            <Input
              id="code"
              type="text"
              name="code"
              inputMode="numeric"
              autoComplete="one-time-code"
              placeholder="000000"
              value={form.data.code}
              onChange={(e) => {
                form.setData('code', e.target.value);
              }}
              aria-invalid={Boolean(errorMessage)}
            />
          </div>

          <div className="flex items-center">
            <Button
              className="w-full font-medium"
              disabled={form.processing || form.data.code === ''}
              aria-busy={form.processing}
            >
              {form.processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
              {form.processing ? 'Verificando…' : 'Verificar'}
            </Button>
          </div>
        </div>
      </form>
    </AuthLayout>
  );
}
