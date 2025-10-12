<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para realizar consultas optimizadas en columnas JSON/JSONB.
 *
 * Proporciona métodos para detectar si la base de datos es PostgreSQL
 * y realizar consultas eficientes en campos JSON/JSONB aprovechando
 * las capacidades específicas de PostgreSQL cuando están disponibles.
 */
final class JsonbQueryService
{
    /**
     * Determina si la base de datos actual es PostgreSQL.
     *
     * @return bool True si es PostgreSQL, false en caso contrario.
     */
    public function isPostgres(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    /**
     * Busca registros donde un campo específico contiene un valor dentro de una estructura JSON/JSONB.
     *
     * @param  Builder  $query  La consulta base a modificar
     * @param  string  $column  La columna JSON/JSONB (por ejemplo, 'requirements_definition')
     * @param  string  $path  La ruta al valor dentro del JSON (por ejemplo, 'requirements.form_groups')
     * @param  string  $operator  El operador para la comparación (por ejemplo, '=', '@>', '?', etc.)
     * @param  mixed  $value  El valor a buscar
     * @return Builder La consulta modificada
     */
    public function whereJsonContains(Builder $query, string $column, string $path, string $operator, mixed $value): Builder
    {
        if ($this->isPostgres()) {
            // PostgreSQL permite consultas más avanzadas con el operador @>
            $jsonPath = $this->buildJsonPath($column, $path);

            return $query->whereRaw("$jsonPath $operator ?", [$this->formatJsonValue($value)]);
        }

        // Para SQLite y MySQL, usamos la sintaxis estándar de Laravel
        $fullPath = $path ? "$column->$path" : $column;

        return $query->where($fullPath, $operator, $value);
    }

    /**
     * Busca registros donde una propiedad de JSONB/JSON tiene un valor específico.
     *
     * @param  Builder|\Illuminate\Database\Eloquent\Builder  $query  La consulta base a modificar
     * @param  string  $jsonColumn  La columna JSON/JSONB
     * @param  string  $property  La propiedad dentro del JSON
     * @param  mixed  $value  El valor a buscar
     * @param  string  $operator  El operador de comparación (por defecto '=')
     * @return mixed La consulta modificada
     */
    public function whereJsonProperty($query, string $jsonColumn, string $property, mixed $value, string $operator = '='): mixed
    {
        if ($this->isPostgres()) {
            // Usamos el operador JSONB específico de PostgreSQL (más eficiente)
            return $query->whereRaw("$jsonColumn->>? $operator ?", [$property, $value]);
        }
        // Fallback a la funcionalidad JSON estándar de Laravel
        if ($operator === '=') {
            return $query->whereJsonContains($jsonColumn, [$property => $value]);
        }

        // Para otros operadores, usamos whereRaw
        return $query->whereRaw("JSON_EXTRACT($jsonColumn, '$.$property') $operator ?", [$value]);

    }

    /**
     * Crea un índice GIN en una columna JSONB (solo PostgreSQL).
     *
     * @param  string  $table  Nombre de la tabla
     * @param  string  $column  Nombre de la columna
     * @param  string|null  $indexName  Nombre del índice (opcional)
     * @return bool Verdadero si se creó el índice, falso si no es PostgreSQL
     */
    public function createJsonbIndex(string $table, string $column, ?string $indexName = null): bool
    {
        if (! $this->isPostgres()) {
            return false;
        }

        $indexName = $indexName ?? "idx_{$table}_{$column}";

        return DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON {$table} USING GIN ({$column})");
    }

    /**
     * Realiza una búsqueda difusa en una propiedad JSONB (solo PostgreSQL).
     *
     * @param  Builder|\Illuminate\Database\Eloquent\Builder  $query  El constructor de consulta
     * @param  string  $jsonColumn  El nombre de la columna JSONB
     * @param  string  $property  La propiedad a buscar dentro del JSONB
     * @param  string  $searchTerm  El término de búsqueda
     * @return mixed La consulta con la condición añadida
     */
    public function whereJsonPropertyLike($query, string $jsonColumn, string $property, string $searchTerm): mixed
    {
        if ($this->isPostgres()) {
            // Búsqueda difusa en PostgreSQL con ILIKE para ignorar mayúsculas/minúsculas
            return $query->whereRaw("$jsonColumn->>? ILIKE ?", [$property, "%{$searchTerm}%"]);
        }

        // Para otros motores, esto es más complejo y menos eficiente
        // Aquí usamos una aproximación, aunque no es óptima para grandes conjuntos de datos
        return $query->whereRaw("JSON_EXTRACT($jsonColumn, '$.$property') LIKE ?", ["%{$searchTerm}%"]);

    }

    /**
     * Busca en múltiples propiedades JSON/JSONB (solo PostgreSQL).
     *
     * @param  Builder|\Illuminate\Database\Eloquent\Builder  $query  El constructor de consulta
     * @param  string  $jsonColumn  El nombre de la columna JSON/JSONB
     * @param  array  $properties  Lista de propiedades a buscar dentro del JSON
     * @param  string  $searchTerm  El término de búsqueda
     * @return mixed La consulta con la condición añadida
     */
    public function searchAcrossJsonProperties($query, string $jsonColumn, array $properties, string $searchTerm): mixed
    {
        if ($this->isPostgres()) {
            $conditions = [];
            $bindings = [];

            foreach ($properties as $property) {
                $conditions[] = "$jsonColumn->>? ILIKE ?";
                $bindings[] = $property;
                $bindings[] = "%{$searchTerm}%";
            }

            return $query->whereRaw('('.implode(' OR ', $conditions).')', $bindings);
        }

        // Para otros motores, hacemos una aproximación
        return $query->where(function ($q) use ($jsonColumn, $properties, $searchTerm) {
            foreach ($properties as $property) {
                $q->orWhereRaw("JSON_EXTRACT($jsonColumn, '$.$property') LIKE ?", ["%{$searchTerm}%"]);
            }
        });

    }

    /**
     * Verifica si una propiedad JSON/JSONB existe.
     *
     * @param  Builder|\Illuminate\Database\Eloquent\Builder  $query  El constructor de consulta
     * @param  string  $jsonColumn  El nombre de la columna JSON/JSONB
     * @param  string  $property  La propiedad a verificar dentro del JSON
     * @param  bool  $exists  Si debe existir o no existir la propiedad
     * @return mixed La consulta con la condición añadida
     */
    public function whereJsonPropertyExists($query, string $jsonColumn, string $property, bool $exists = true): mixed
    {
        if ($this->isPostgres()) {
            $operator = $exists ? 'IS NOT NULL' : 'IS NULL';

            return $query->whereRaw("$jsonColumn->?::text $operator", [$property]);
        }
        if ($exists) {
            return $query->whereRaw("JSON_EXTRACT($jsonColumn, '$.$property') IS NOT NULL");
        }

        return $query->whereRaw("JSON_EXTRACT($jsonColumn, '$.$property') IS NULL");

    }

    /**
     * Ordena los resultados por una propiedad JSON/JSONB.
     *
     * @param  Builder|\Illuminate\Database\Eloquent\Builder  $query  El constructor de consulta
     * @param  string  $jsonColumn  El nombre de la columna JSON/JSONB
     * @param  string  $property  La propiedad por la que ordenar
     * @param  string  $direction  Dirección del ordenamiento ('asc' o 'desc')
     * @return mixed La consulta con el ordenamiento añadido
     */
    public function orderByJsonProperty($query, string $jsonColumn, string $property, string $direction = 'asc'): mixed
    {
        if ($this->isPostgres()) {
            // Para PostgreSQL, convertimos el valor a texto para un ordenamiento más consistente
            return $query->orderByRaw("$jsonColumn->>? $direction", [$property]);
        }

        // Para otros motores
        return $query->orderByRaw("JSON_EXTRACT($jsonColumn, '$.$property') $direction");

    }

    /**
     * Construye una ruta JSON para PostgreSQL.
     *
     * @param  string  $column  La columna base
     * @param  string  $path  La ruta dentro del JSON
     * @return string La expresión de ruta completa para PostgreSQL
     */
    private function buildJsonPath(string $column, string $path): string
    {
        if (empty($path)) {
            return $column;
        }

        $segments = explode('.', $path);
        $expression = $column;

        foreach ($segments as $segment) {
            if (is_numeric($segment)) {
                $expression .= "->$segment";
            } else {
                $expression .= "->'$segment'";
            }
        }

        return $expression;
    }

    /**
     * Formatea un valor para usarlo en consultas JSONB.
     *
     * @param  mixed  $value  El valor a formatear
     * @return string El valor formateado para JSON
     */
    private function formatJsonValue(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }
}
