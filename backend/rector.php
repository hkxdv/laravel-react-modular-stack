<?php

declare(strict_types=1);

use Pest\Rector\Set\PestSetList;
use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use RectorLaravel\Rector\Class_\AddHasFactoryToModelsRector;
use RectorLaravel\Set\LaravelSetList;
use RectorLaravel\Set\LaravelSetProvider;

return RectorConfig::configure()
    ->withSetProviders(LaravelSetProvider::class)
    ->withSets([
        LaravelSetList::LARAVEL_ARRAYACCESS_TO_METHOD_CALL,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_CONTAINER_STRING_TO_FULLY_QUALIFIED_NAME,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
        LaravelSetList::LARAVEL_FACTORIES,
        LaravelSetList::LARAVEL_IF_HELPERS,
        LaravelSetList::LARAVEL_LEGACY_FACTORIES_TO_CLASSES,
        LaravelSetList::LARAVEL_TESTING,
        LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
        PestSetList::CODING_STYLE,
    ])
    ->withComposerBased(laravel: true)
    ->withCache(
        cacheDirectory: __DIR__.'/bootstrap/cache/rector',
        cacheClass: FileCacheStorage::class,
    )
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/Modules',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/bootstrap/modules',
        __DIR__.'/bootstrap/providers.php',
        __DIR__.'/config',
        __DIR__.'/../database',
        __DIR__.'/public',
        __DIR__.'/routes',
    ])
    ->withSkip([
        RectorLaravel\Rector\ArrayDimFetch\EnvVariableToEnvHelperRector::class => [
            __DIR__.'/bootstrap/app.php',
        ],
        RectorLaravel\Rector\ArrayDimFetch\ServerVariableToRequestFacadeRector::class => [
            __DIR__.'/bootstrap/app.php',
            __DIR__.'/bootstrap/modules/env.php',
        ],
        AddHasFactoryToModelsRector::class => [
            __DIR__.'/Modules/Core/src/Infrastructure/Eloquent/Models/AbstractDomainUser.php',
        ],
        Pest\Rector\Rules\Pest2ToPest3\UsesToExtendRector::class,
    ])
    ->withConfiguredRule(Pest\Rector\Rules\ChainExpectCallsRector::class, [
        'merge_different_variables' => false,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        codingStyle: true,
    )
    ->withAttributesSets()
    // Set explicit PHP version to avoid composer.json lookup from CWD
    ->withPhpSets(php84: true)
    // Cap parallel workers and memory to avoid saturating the machine on ~2.5k files
    ->withParallel(
        timeoutSeconds: 120,
        maxNumberOfProcess: 4,
        jobSize: 20,
    )
    ->withMemoryLimit('512M');
