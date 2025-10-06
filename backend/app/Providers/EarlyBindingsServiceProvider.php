<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\CacheManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\FileLoader as TranslationFileLoader;
use Illuminate\Translation\Translator as TranslationTranslator;

class EarlyBindingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind temprano para 'cache' para evitar fallos en procesos de arranque/descubrimiento
        if (!$this->app->bound('cache')) {
            $this->app->singleton('cache', function ($app) {
                return new CacheManager($app);
            });
        }

        // Asegurar driver de caché por defecto si no está configurado
        $config = $this->app->make('config');
        if ($config->get('cache.default') === null) {
            $config->set('cache.default', 'array');
        }

        // Bind mínimo de 'translator' para prevenir errores durante el descubrimiento de paquetes
        if (!$this->app->bound('translator')) {
            $this->app->singleton('translator', function ($app) {
                $langPath = base_path('resources/lang');
                $loader = new TranslationFileLoader(new Filesystem, $langPath);
                $locale = ($app->has('config') && $app['config']->get('app.locale')) ? $app['config']->get('app.locale') : 'en';
                $translator = new TranslationTranslator($loader, $locale);
                $fallback = ($app->has('config') && $app['config']->get('app.fallback_locale')) ? $app['config']->get('app.fallback_locale') : 'en';
                $translator->setFallback($fallback);

                return $translator;
            });
        }
    }
}
