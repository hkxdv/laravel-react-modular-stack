<?php

declare(strict_types=1);

namespace Foundry\Helpers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

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

/**
 * Obtiene un atributo de un modelo Eloquent como string, con valor por defecto.
 *
 * @param  Model  $model  Instancia del modelo Eloquent.
 * @param  string  $attribute  Nombre del atributo.
 * @param  string  $default  Valor por defecto cuando el atributo no es string.
 * @return string Valor del atributo como string o el valor por defecto.
 */
function modelStringAttribute(Model $model, string $attribute, string $default = ''): string
{
    $value = $model->getAttribute($attribute);

    return is_string($value) ? $value : $default;
}

/**
 * Obtiene un valor de configuración como booleano.
 *
 * Semántica estricta: devuelve el valor solo cuando es bool nativo; en caso
 * contrario devuelve el valor por defecto. Las cadenas '1'/'0'/'true' no se
 * convierten automáticamente.
 *
 * @param  string  $key  Clave de configuración en notación de punto.
 * @param  bool  $default  Valor por defecto cuando la clave falta o no es bool.
 * @return bool Valor de configuración normalizado a bool.
 */
function configBool(string $key, bool $default = false): bool
{
    $value = config($key);

    return is_bool($value) ? $value : $default;
}

/**
 * Obtiene un offset de un array como string no vacío.
 *
 * Útil al construir DTOs a partir de arrays de configuración declarativa,
 * eliminando bloques `isset(...) && is_string(...)`.
 *
 * @param  array<string, mixed>  $data  Array de origen.
 * @param  string  $key  Clave del offset.
 * @param  string  $default  Valor por defecto cuando la clave falta, no es string o está vacía.
 * @return string Offset normalizado a string.
 */
function arrayString(array $data, string $key, string $default = ''): string
{
    $value = $data[$key] ?? null;

    return is_string($value) && $value !== '' ? $value : $default;
}

/**
 * Obtiene un offset de un array como string nullable.
 *
 * Devuelve null cuando la clave falta, no es string o es cadena vacía.
 *
 * @param  array<string, mixed>  $data  Array de origen.
 * @param  string  $key  Clave del offset.
 * @return string|null Offset como string o null.
 */
function arrayNullableString(array $data, string $key): ?string
{
    $value = $data[$key] ?? null;

    return is_string($value) && $value !== '' ? $value : null;
}

/**
 * Obtiene un offset de un array como entero.
 *
 * @param  array<string, mixed>  $data  Array de origen.
 * @param  string  $key  Clave del offset.
 * @param  int  $default  Valor por defecto cuando la clave falta o no es numérica.
 * @return int Offset normalizado a entero.
 */
function arrayInt(array $data, string $key, int $default = 0): int
{
    $value = $data[$key] ?? null;

    return is_numeric($value) ? (int) $value : $default;
}

/**
 * Obtiene un offset de un array como booleano.
 *
 * Semántica estricta, igual que configBool.
 *
 * @param  array<string, mixed>  $data  Array de origen.
 * @param  string  $key  Clave del offset.
 * @param  bool  $default  Valor por defecto cuando la clave falta o no es bool.
 * @return bool Offset normalizado a bool.
 */
function arrayBool(array $data, string $key, bool $default = false): bool
{
    $value = $data[$key] ?? null;

    return is_bool($value) ? $value : $default;
}

/**
 * Filtra una lista de strings a partir de un valor mixed.
 *
 * Cubre el patrón `is_array($x) ? array_filter($x, 'is_string') : []` que
 * aparece al consumir permisos anidados u offsets de configuración.
 *
 * @param  mixed  $value  Valor potencialmente iterable de strings.
 * @return list<string> Lista de strings válidos.
 */
function stringList(mixed $value): array
{
    if (! is_array($value)) {
        return [];
    }

    $list = [];
    foreach ($value as $item) {
        if (is_string($item)) {
            $list[] = $item;
        }
    }

    return $list;
}

/**
 * Valida que un valor sea string, lanzando una excepción en caso contrario.
 *
 * A diferencia de configString y arrayString, no aplica valor por defecto:
 * falla inmediatamente en valores que deben ser string. Tras la llamada,
 * PHPStan considera la variable original como string, sin necesidad de
 * bloques if adicionales.
 *
 * @param  mixed  $value  Valor a validar.
 * @param  string  $message  Mensaje de la excepción cuando la validación falla.
 * @return string Valor validado como string.
 *
 * @phpstan-assert string $value
 *
 * @throws InvalidArgumentException Cuando el valor no es string.
 */
function assertString(mixed $value, string $message = ''): string
{
    if (! is_string($value)) {
        throw new InvalidArgumentException($message !== '' ? $message : 'Expected string value.');
    }

    return $value;
}

/**
 * Valida que un valor sea instancia de una clase, lanzando una excepción en caso contrario.
 *
 * @template T of object
 *
 * @param  mixed  $value  Valor a validar.
 * @param  class-string<T>  $class  Clase esperada.
 * @param  string  $message  Mensaje de la excepción cuando la validación falla.
 * @return T Instancia validada del tipo esperado.
 *
 * @phpstan-assert T $value
 *
 * @throws InvalidArgumentException Cuando el valor no es instancia de la clase.
 */
function assertInstanceOf(mixed $value, string $class, string $message = ''): object
{
    if (! $value instanceof $class) {
        throw new InvalidArgumentException($message !== '' ? $message : "Expected instance of {$class}.");
    }

    return $value;
}
