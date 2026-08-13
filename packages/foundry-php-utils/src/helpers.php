<?php

declare(strict_types=1);

namespace Foundry\Helpers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;

/**
 * Obtiene un valor de configuración como string no vacío.
 *
 * @param  string  $key  Clave de configuración en notación de punto.
 * @param  string  $default  Valor por defecto cuando la clave falta o está vacía.
 * @return string Valor de configuración normalizado.
 */
function configString(string $key, string $default = ''): string
{
    $value = config($key);

    return is_string($value) && $value !== '' ? $value : $default;
}

/**
 * Obtiene un valor de configuración como entero.
 *
 * @param  string  $key  Clave de configuración en notación de punto.
 * @param  int  $default  Valor por defecto cuando la clave falta o no es numérica.
 * @return int Valor de configuración convertido a entero.
 */
function configInt(string $key, int $default = 0): int
{
    $value = config($key);

    return is_numeric($value) ? (int) $value : $default;
}

/**
 * Obtiene un valor de configuración como string nullable.
 *
 * Devuelve null cuando la clave falta o el valor es una cadena vacía.
 *
 * @param  string  $key  Clave de configuración en notación de punto.
 * @return string|null Valor de configuración o null.
 */
function configNullableString(string $key): ?string
{
    $value = config($key);

    return is_string($value) && $value !== '' ? $value : null;
}

/**
 * Obtiene un valor de configuración como array.
 *
 * @param  string  $key  Clave de configuración en notación de punto.
 * @param  array<string, mixed>  $default  Valor por defecto cuando la clave falta o no es un array.
 * @return array<string, mixed> Valor de configuración normalizado a array.
 */
function configArray(string $key, array $default = []): array
{
    $value = config($key);

    return is_array($value) ? $value : $default;
}

/**
 * Obtiene un valor de caché como entero.
 *
 * @param  string  $key  Clave de caché.
 * @param  int  $default  Valor por defecto cuando la clave falta o no es numérica.
 * @return int Valor almacenado convertido a entero.
 */
function cacheInt(string $key, int $default = 0): int
{
    $value = Cache::get($key, $default);

    if (is_int($value)) {
        return $value;
    }

    if (is_numeric($value)) {
        return (int) $value;
    }

    return $default;
}

/**
 * Obtiene un valor de caché como string no vacío.
 *
 * @param  string  $key  Clave de caché.
 * @param  string  $default  Valor por defecto cuando la clave falta o está vacía.
 * @return string Valor almacenado normalizado.
 */
function cacheString(string $key, string $default = ''): string
{
    $value = Cache::get($key, $default);

    return is_string($value) && $value !== '' ? $value : $default;
}

/**
 * Obtiene un valor de caché como array.
 *
 * @param  string  $key  Clave de caché.
 * @param  array<string, mixed>  $default  Valor por defecto cuando la clave falta o no es un array.
 * @return array<string, mixed> Valor almacenado normalizado a array.
 */
function cacheArray(string $key, array $default = []): array
{
    $value = Cache::get($key, $default);

    return is_array($value) ? $value : $default;
}

/**
 * Resuelve el identificador de un usuario autenticable de forma segura.
 *
 * @param  Authenticatable|null  $user  Usuario autenticado o null.
 * @param  string  $fallback  Valor por defecto cuando el usuario es null o no tiene identificador válido.
 * @return string Identificador del usuario o valor por defecto.
 */
function userId(?Authenticatable $user, string $fallback = 'anonymous'): string
{
    $raw = $user?->getAuthIdentifier();

    return is_string($raw) || is_int($raw) ? (string) $raw : $fallback;
}

/**
 * Obtiene la fecha de modificación de un archivo como timestamp.
 *
 * Devuelve 0 cuando el archivo no existe o no es accesible.
 *
 * @param  string  $path  Ruta absoluta del archivo.
 * @return int Timestamp de modificación o 0 en caso de error.
 */
function fileModificationTime(string $path): int
{
    if (! is_file($path)) {
        return 0;
    }

    $mtime = false;
    set_error_handler(static fn (): bool => true);
    try {
        $mtime = filemtime($path);
    } finally {
        restore_error_handler();
    }

    return $mtime === false ? 0 : (int) $mtime;
}
