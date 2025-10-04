<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectStaticAssets
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Verificar el referer para asegurarse de que proviene de nuestro dominio
        $referer = $request->headers->get('referer');
        $allowedDomains = [
            config('app.url'),
            // Puedes agregar más dominios permitidos aquí
            // 'https://otro-dominio-permitido.com'
        ];
        
        // Para entornos de desarrollo, permitir localhost y dominios de desarrollo
        if (app()->environment('local', 'development')) {
            $allowedDomains[] = 'http://localhost';
            $allowedDomains[] = 'http://127.0.0.1';
        }
        
        $isAllowed = false;
        
        // Si no hay referer (acceso directo) o proviene de un dominio permitido
        if (!$referer) {
            // Permitir acceso directo (sin referer) - opcional, puedes cambiarlo a false si quieres ser más estricto
            $isAllowed = true;
        } else {
            // Verificar si el referer proviene de un dominio permitido
            foreach ($allowedDomains as $domain) {
                if (str_starts_with($referer, $domain)) {
                    $isAllowed = true;
                    break;
                }
            }
        }
        
        if (!$isAllowed) {
            abort(403, 'Acceso no autorizado a este recurso.');
        }
        
        return $next($request);
    }
} 