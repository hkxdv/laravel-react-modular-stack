<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para gestionar el restablecimiento de contraseñas.
 *
 * Este controlador maneja la fase final del proceso de restablecimiento de contraseña,
 * mostrando el formulario y procesando la nueva contraseña del usuario.
 */
final class NewPasswordController extends Controller
{
    /**
     * Muestra la vista para restablecer la contraseña.
     *
     * Renderiza el formulario donde el usuario puede introducir su nueva contraseña,
     * pasando el correo electrónico y el token de la solicitud a la vista.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Maneja la solicitud entrante para restablecer la contraseña.
     *
     * Valida los datos y utiliza el "broker" de contraseñas de Laravel para verificar
     * el token y actualizar la contraseña del usuario de forma segura.
     *
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Se utiliza el broker `Password` de Laravel para manejar el proceso de restablecimiento.
        // Este método abstrae la lógica de verificar el token y encontrar al usuario asociado.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Se dispara el evento `PasswordReset` para que otros listeners puedan reaccionar (ej. enviar notificación).
                event(new PasswordReset($user));
            }
        );

        // Si el broker confirma que la contraseña fue reseteada (`PASSWORD_RESET`),
        // se redirige al usuario a la página de login con un mensaje de estado traducido.
        if ($status === Password::PASSWORD_RESET) {
            return to_route('login')->with('status', __($status));
        }

        // Si el broker devuelve un estado de error (ej. token inválido o usuario no encontrado),
        // se lanza una `ValidationException` para mostrar el error en el formulario.
        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
