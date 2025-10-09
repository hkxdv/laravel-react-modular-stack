<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class RuntimeConfigServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Ajustes de entorno y PHP en tiempo de ejecución
        @ini_set('memory_limit', '512M');
        @ini_set('upload_max_filesize', '10M');
        @ini_set('post_max_size', '10M');

        $timezone = (string) (config('app.timezone') ?? 'UTC');
        if ($timezone !== '') {
            @date_default_timezone_set($timezone);
        }

        // Forzar HTTPS en producción si se habilita explícitamente desde config
        if (app()->isProduction() && filter_var((bool) config('app.force_https', false), FILTER_VALIDATE_BOOL)) {
            URL::forceScheme('https');
        }
    }
}
