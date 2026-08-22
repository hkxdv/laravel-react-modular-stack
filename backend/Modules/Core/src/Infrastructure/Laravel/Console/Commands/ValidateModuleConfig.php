<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Infrastructure\Laravel\Services\ModuleConfigValidator;

/**
 * Valida las configuraciones de todos los módulos registrados.
 */
final class ValidateModuleConfig extends Command
{
    protected $signature = 'modules:validate-config {--strict : Promueve warnings a failures}';

    protected $description = 'Valida las configuraciones de módulos contra las reglas de integridad';

    public function __construct(
        private readonly ModuleConfigValidator $validator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $strict = (bool) $this->option('strict');
        $result = $this->validator->validateAll($strict);

        foreach ($result->failures() as $failure) {
            /** @var array{module: string, rule: string, severity: string, message: string} $failure */
            $icon = match ($failure['severity']) {
                'fail' => 'FAIL',
                'warn' => 'WARN',
                default => 'PASS',
            };

            $this->line(sprintf(
                '  %s [%s] %s: %s',
                $icon,
                $failure['module'],
                $failure['rule'],
                $failure['message'],
            ));
        }

        $failed = $result->hasFailures();
        $warned = $result->hasWarnings();

        if ($failed) {
            $this->error(sprintf('Validation failed with %d error(s).', $result->failureCount()));
        } elseif ($warned) {
            $this->warn(sprintf('Validation passed with %d warning(s).', $result->warningCount()));
        } else {
            $this->info('All validations passed.');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
