<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends BaseSettingsController
{
    /**
     * Muestra la página de configuración de la contraseña del usuario.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/password', [
            'contextualNavItems' => $this->getSettingsNavigationItems(),
        ]);
    }

    /**
     * Actualiza la contraseña del usuario.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back();
    }
}
