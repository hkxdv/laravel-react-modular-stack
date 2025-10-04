<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends BaseSettingsController
{
    /**
     * Muestra la página de configuración del perfil del usuario.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'contextualNavItems' => $this->getSettingsNavigationItems(),
        ]);
    }

    /**
     * Actualiza la configuración del perfil del usuario.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }
        $request->user()->save();

        return to_route('internal.settings.profile.edit');
    }

    /**
     * Elimina la cuenta del usuario.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        FacadesAuth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * [REMOVIDO] Perfil de contacto público.
     * Esta funcionalidad ha sido eliminada junto con el modelo ContactStaffUser.
     * Se mantiene el método como no-op para evitar referencias rotas si quedaran rutas/llamadas antiguas.
     */
    public function updateContactProfile(Request $request): RedirectResponse
    {
        return back()->with('status', 'contact-profile-removed');
    }

    /**
     * [REMOVIDO] Subida de imagen de perfil público.
     */
    public function uploadProfileImage(Request $request): RedirectResponse
    {
        return back()->with('status', 'profile-image-feature-removed');
    }

    /**
     * [REMOVIDO] Eliminación de imagen de perfil público.
     */
    public function deleteProfileImage(Request $request): RedirectResponse
    {
        return back()->with('status', 'profile-image-feature-removed');
    }
}
