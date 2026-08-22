<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Validation;

/**
 * Resultado de validación de configuraciones de módulos.
 */
final readonly class ValidationResult
{
    /**
     * @param  array<int, array{module: string, rule: string, severity: string, message: string}>  $entries
     */
    public function __construct(
        private array $entries,
    ) {
        //
    }

    /**
     * Devuelve solo las entradas que no son 'pass'.
     *
     * @return array<int, array{module: string, rule: string, severity: string, message: string}>
     */
    public function failures(): array
    {
        return array_values(array_filter(
            $this->entries,
            static fn (array $e): bool => $e['severity'] !== 'pass'
        ));
    }

    public function hasFailures(): bool
    {
        return $this->failureCount() > 0;
    }

    public function failureCount(): int
    {
        return count(array_filter(
            $this->entries,
            static fn (array $e): bool => $e['severity'] === 'fail'
        ));
    }

    public function hasWarnings(): bool
    {
        return $this->warningCount() > 0;
    }

    public function warningCount(): int
    {
        return count(array_filter(
            $this->entries,
            static fn (array $e): bool => $e['severity'] === 'warn'
        ));
    }
}
