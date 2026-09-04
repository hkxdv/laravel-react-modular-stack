import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import type { UrlMethodPair } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import { usePasskeyVerify } from '@laravel/passkeys/react';
import { KeyRound, LoaderCircle } from 'lucide-react';

interface Props {
  routes?: {
    options: UrlMethodPair;
    submit: UrlMethodPair;
  };
  label?: string;
  loadingLabel?: string;
}

/**
 * Botón "Iniciar sesión con passkey" (WebAuthn).
 *
 * Patrón del starter kit de Laravel: usa usePasskeyVerify y navega al
 * dashboard con la respuesta del backend tras la verificación.
 */
export default function PasskeyVerify({ routes, label, loadingLabel }: Props = {}) {
  const { verify, isLoading, error, isSupported } = usePasskeyVerify({
    ...(routes && {
      routes: {
        options: routes.options.url,
        submit: routes.submit.url,
      },
    }),
    onSuccess: (response) => {
      router.visit(response.redirect ?? '/dashboard');
    },
  });

  if (!isSupported) {
    return null;
  }

  return (
    <div className="grid gap-2">
      <Button
        type="button"
        variant="outline"
        className="w-full"
        onClick={() => void verify()}
        disabled={isLoading}
      >
        {isLoading ? (
          <LoaderCircle className="h-4 w-4 animate-spin pr-1" />
        ) : (
          <KeyRound className="h-4 w-4 pr-1" />
        )}
        {isLoading ? (loadingLabel ?? 'Autenticando…') : (label ?? 'Iniciar sesión con passkey')}
      </Button>
      {error && <InputError message={error} className="text-center" />}
    </div>
  );
}
