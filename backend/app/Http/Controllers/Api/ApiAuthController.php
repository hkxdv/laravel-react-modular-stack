<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Controlador para gestionar la autenticación de la API.
 *
 * Maneja el inicio de sesión de usuarios y la obtención de datos del usuario autenticado
 * a través de tokens de API de Sanctum.
 */
class ApiAuthController extends Controller
{
    /**
     * Maneja la solicitud de inicio de sesión y genera un token de API.
     *
     * Valida las credenciales del usuario, verifica el estado de su correo electrónico
     * y, si todo es correcto, emite un nuevo token de Sanctum. El `device_name`
     * se utiliza para identificar el token y revocar tokens antiguos del mismo dispositivo.
     *
     * @param  \Illuminate\Http\Request  $request  La solicitud HTTP con las credenciales.
     * @return \Illuminate\Http\JsonResponse La respuesta JSON con el token de acceso.
     *
     * @throws \Illuminate\Validation\ValidationException Si la validación falla.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = StaffUsers::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Debes verificar tu correo electrónico antes de continuar.'],
            ]);
        }

        $user->tokens()->where('name', $request->device_name)->delete();
        $token = $user->createToken($request->device_name, ['basic'], now()->addDays(30));

        Log::info('Token API generado', [
            'user_id' => $user->id,
            'device' => $request->device_name,
            'ip' => $request->ip(),
        ]);

        return response()->json(['token' => $token->plainTextToken]);
    }

    /**
     * Obtiene la información del usuario actualmente autenticado.
     *
     * @param  \Illuminate\Http\Request  $request  La solicitud HTTP.
     * @return array Un array con los datos públicos del usuario.
     */
    public function user(Request $request): array
    {
        return $request->user()->only([
            'id',
            'name',
            'email',
            'created_at',
            'updated_at',
            'email_verified_at',
        ]);
    }
}
