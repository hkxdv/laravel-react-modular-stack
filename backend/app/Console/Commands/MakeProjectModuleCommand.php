<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class MakeProjectModuleCommand extends Command
{
    /**
     * El nombre y la firma de la consola del comando.
     *
     * @var string
     */
    protected $signature = 'make:project-module {name : Nombre del módulo en formato ModuleXX}';

    /**
     * La descripción de la consola del comando.
     *
     * @var string
     */
    protected $description = 'Crea un módulo con la estructura y estándares del proyecto';

    /**
     * Ejecuta el comando de consola.
     */
    public function handle()
    {
        $moduleName = $this->argument('name');

        // Validar que el nombre del módulo siga el formato ModuleXX
        if (!preg_match('/^Module\d{2,}$/', $moduleName)) {
            $this->error('El nombre del módulo debe tener el formato ModuleXX (ej. Module01, Module02, Module10, etc.)');

            return 1;
        }

        $this->info("Creando estructura para el módulo {$moduleName}...");

        $basePath = base_path("Modules/{$moduleName}");
        $lowerName = Str::lower($moduleName);
        // $kebabName = Str::kebab($moduleName); // Línea original
        $kebabName = Str::lower(preg_replace('/(?<!^)(?=[A-Z0-9])/', '-', $moduleName));
        // Si empieza con "module-", lo mantenemos, si no, añadimos "module-" al principio y luego el resto en kebab
        if (Str::startsWith($moduleName, 'Module')) {
            // Extraer la parte después de "Module"
            $suffix = Str::substr($moduleName, 6); // Longitud de "Module"
            // Convertir el sufijo a kebab-case y asegurarse de que los números estén separados por guiones
            $kebabSuffix = Str::lower(preg_replace('/(?<=\D)(?=\d)|(?<=\d)(?=\D)|(?<=[a-z])(?=[A-Z])/', '-', $suffix));
            $kebabName = 'module-' . $kebabSuffix;
        } else {
            // Para nombres de módulo que no empiezan con "Module", aplicar un kebab case general
            $kebabName = Str::kebab($moduleName);
        }
        $studlyName = Str::studly($moduleName);
        $functionalNamePlaceholder = $studlyName; // Fallback si no se pide para el frontend

        // Eliminar directorio existente si confirmation es 'yes' o 'y' y existe
        if (File::exists($basePath)) {
            $confirm = $this->ask("El módulo {$moduleName} ya existe. ¿Deseas eliminarlo y continuar? (yes/no)", 'no');
            if (Str::lower($confirm) === 'yes' || Str::lower($confirm) === 'y') {
                $this->info("Eliminando directorio existente: {$basePath}");
                File::deleteDirectory($basePath);
            } else {
                $this->info('Operación cancelada.');

                return 0;
            }
        }

        // Crear directorios principales
        $directories = [
            'app/Http/Controllers',
            'app/Http/Middleware',
            'app/Http/Requests',
            'app/Models',
            'app/Providers',
            'config',
            'database/factories',
            'database/migrations',
            'database/seeders',
            'routes',
        ];

        foreach ($directories as $dir) {
            $path = "{$basePath}/{$dir}";
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
                $this->line('Directorio creado: ' . str_replace(base_path(), '', $path));

                // Añadir .gitkeep si el directorio está en la lista de directorios vacíos comunes
                $emptyDirectories = [
                    'app/Http/Middleware',
                    'app/Http/Requests',
                    'app/Models',
                    'database/factories',
                    'database/migrations',
                ];

                if (in_array($dir, $emptyDirectories)) {
                    File::put("{$path}/.gitkeep", '');
                    $this->line('Archivo creado: ' . str_replace(base_path(), '', "{$path}/.gitkeep"));
                }
            }
        }

        // Definir rutas a los stubs y destinos de los archivos generados
        $stubsPath = base_path('stubs/new-module-custom/');

        $filesToGenerate = [
            'composer.json' => ['stub' => 'composer.stub', 'dest' => $basePath . '/composer.json'],
            'module.json' => ['stub' => 'module.stub', 'dest' => $basePath . '/module.json'],
            'config/config.php' => ['stub' => 'config.stub', 'dest' => $basePath . '/config/config.php'],
            'routes/web.php' => ['stub' => 'routes.stub', 'dest' => $basePath . '/routes/web.php'],
            'app/Providers/' . $studlyName . 'ServiceProvider.php' => ['stub' => 'provider.stub', 'dest' => $basePath . '/app/Providers/' . $studlyName . 'ServiceProvider.php'],
            'app/Providers/RouteServiceProvider.php' => ['stub' => 'route-provider.stub', 'dest' => $basePath . '/app/Providers/RouteServiceProvider.php'],
            'app/Http/Controllers/' . $studlyName . 'Controller.php' => ['stub' => 'controller.stub', 'dest' => $basePath . '/app/Http/Controllers/' . $studlyName . 'Controller.php'],
            'database/seeders/' . $studlyName . 'DatabaseSeeder.php' => ['stub' => 'seeder.stub', 'dest' => $basePath . '/database/seeders/' . $studlyName . 'DatabaseSeeder.php'],
        ];

        // Placeholder replacements
        $replacements = [
            '$STUDLY_NAME$' => $studlyName,
            '$LOWER_NAME$' => $lowerName,
            '$KEBAB_NAME$' => $kebabName,
            '$VENDOR_LOWER$' => config('modules.composer.vendor', 'module'),
            '$MODULE_NAMESPACE$' => "Modules\\{$studlyName}\\App",
            '$CONTROLLER_NAMESPACE$' => "Modules\\{$studlyName}\\App\\Http\\Controllers",
            '$PROVIDER_NAMESPACE$' => "Modules\\{$studlyName}\\App\\Providers",
            '$FUNCTIONAL_NAME$' => $functionalNamePlaceholder,
        ];

        // Generar archivos desde stubs
        foreach ($filesToGenerate as $generatedFileName => $fileInfo) {
            $stubPath = $stubsPath . $fileInfo['stub'];
            $destPath = $fileInfo['dest'];

            if (!File::exists($stubPath)) {
                $this->warn("Stub no encontrado: {$stubPath}. Este archivo no se generará.");

                continue;
            }

            $stubContent = File::get($stubPath);

            // Aplicar reemplazos
            $fileContent = strtr($stubContent, $replacements);

            // Crear el archivo
            File::put($destPath, $fileContent);
            $this->line('Archivo generado: ' . str_replace(base_path(), '', $destPath));
        }

        // Asegurar que el módulo esté activado en modules_statuses.json
        $this->info('Actualizando modules_statuses.json...');
        $statusesPath = base_path('modules_statuses.json');
        $statuses = File::exists($statusesPath) ? json_decode(File::get($statusesPath), true) : [];
        $statuses[$studlyName] = true; // Asegurar que el módulo esté activo
        File::put($statusesPath, json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('modules_statuses.json actualizado.');

        // Ejecutar composer dump-autoload
        $this->info('Actualizando autoload de Composer...');
        $process = Process::path(dirname(__DIR__, 3))
            ->timeout(120)
            ->run('composer dump-autoload');

        if ($process->successful()) {
            $this->info('Autoload actualizado correctamente.');
        } else {
            $this->warn('Error al actualizar autoload: ' . $process->errorOutput());
            $this->warn("Por favor, ejecuta 'cd backend && composer dump-autoload' manualmente si encuentras problemas.");
        }

        $this->info("\n¡Módulo {$moduleName} creado y configurado exitosamente!");

        return 0;
    }
}
