<?php

declare(strict_types=1);

namespace Modules\Examples\App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Examples\App\Http\Requests\TenantLoginRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Controlador esquelético de autenticación para usuarios tenant.
 *
 * Demuestra el flujo completo de login/logout bajo el guard 'tenant'
 * sin depender de controladores específicos de staff.
 */
final class TenantAuthController extends AbstractExamplesController
{
    /**
     * Muestra el formulario de login para usuarios tenant.
     */
    public function create(): Response
    {
        $errors = session('errors');
        $errorMessages = (object) [];
        if ($errors instanceof \Illuminate\Support\ViewErrorBag) {
            $errorMessages = (object) $errors->getBag('default')->getMessages();
        }

        return Inertia::render('auth/login', [
            'canResetPassword' => false,
            'status' => session('status'),
            'errors' => $errorMessages,
        ]);
    }

    /**
     * Autentica a un usuario tenant.
     *
     * @param  TenantLoginRequest  $request  La solicitud validada.
     */
    public function store(TenantLoginRequest $request): SymfonyResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $intended = redirect()->intended(route('internal.tenant.examples.index'))->getTargetUrl();

        return Inertia::location($intended);
    }

    /**
     * Cierra la sesión de un usuario tenant.
     */
    public function destroy(\Illuminate\Http\Request $request): RedirectResponse
    {
        Auth::guard('tenant')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return to_route('tenant.login')
            ->with('status', 'Tu sesión tenant ha sido cerrada correctamente');
    }
}
