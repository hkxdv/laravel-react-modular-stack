<?php

declare(strict_types=1);

return [
    App\Providers\EarlyBindingsServiceProvider::class,
    App\Providers\RuntimeConfigServiceProvider::class,
    Illuminate\Cache\CacheServiceProvider::class,
    Illuminate\Translation\TranslationServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\SessionServiceProvider::class,
];
