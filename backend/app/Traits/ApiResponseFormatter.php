<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait ApiResponseFormatter
 * Proporciona métodos estandarizados para generar respuestas JSON para APIs.
 */
trait ApiResponseFormatter
{
    /**
     * Genera una respuesta JSON exitosa.
     *
     * @param  mixed  $data  Los datos a incluir en la respuesta
     * @param  string|null  $message  Mensaje opcional
     * @param  int  $statusCode  Código HTTP de estado
     * @param  array  $meta  Metadatos adicionales
     */
    protected function successResponse($data = null, ?string $message = null, int $statusCode = 200, array $meta = []): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($message) {
            $response['message'] = $message;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Genera una respuesta JSON de error.
     *
     * @param  string  $message  Mensaje de error
     * @param  int  $statusCode  Código HTTP de estado
     * @param  array  $errors  Errores detallados (opcional)
     */
    protected function errorResponse(string $message, int $statusCode, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Genera una respuesta JSON con paginación.
     *
     * @param  mixed  $data  Datos paginados
     * @param  array  $paginationInfo  Información de paginación
     * @param  string|null  $message  Mensaje opcional
     * @param  int  $statusCode  Código HTTP de estado
     */
    protected function paginatedResponse($data, array $paginationInfo, ?string $message = null, int $statusCode = 200): JsonResponse
    {
        return $this->successResponse($data, $message, $statusCode, $paginationInfo);
    }
}
