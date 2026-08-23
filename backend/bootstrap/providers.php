<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\EarlyBindingsServiceProvider::class,
    App\Providers\RuntimeConfigServiceProvider::class,
    App\Providers\SessionServiceProvider::class,
    App\Providers\TypeScriptTransformerServiceProvider::class,
    Illuminate\Cache\CacheServiceProvider::class,
    Illuminate\Translation\TranslationServiceProvider::class,
    Modules\Admin\App\Providers\AdminServiceProvider::class,
    Modules\Examples\App\Providers\ExamplesServiceProvider::class,
    NunoMaduro\Essentials\EssentialsServiceProvider::class,
];
