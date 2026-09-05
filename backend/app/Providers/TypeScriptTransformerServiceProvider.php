<?php

declare(strict_types=1);

namespace App\Providers;

use Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider as BaseTypeScriptTransformerServiceProvider;
use Spatie\TypeScriptTransformer\Transformers\AttributedClassTransformer;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfigFactory;
use Spatie\TypeScriptTransformer\Writers\GlobalNamespaceWriter;

final class TypeScriptTransformerServiceProvider extends BaseTypeScriptTransformerServiceProvider
{
    protected function configure(TypeScriptTransformerConfigFactory $config): void
    {
        $config
            ->outputDirectory(dirname(__DIR__, 2).'/../frontend/src/types/generated')
            ->transformer(AttributedClassTransformer::class)
            ->transformer(EnumTransformer::class)
            ->transformDirectories(
                dirname(__DIR__, 2).'/Modules/Core/src/Domain',
                dirname(__DIR__, 2).'/Modules/Core/src/Application/View',
                dirname(__DIR__, 2).'/Modules/Admin/app/DTO',
                dirname(__DIR__, 2).'/Modules/Examples/app/DTO',
            )
            ->writer(new GlobalNamespaceWriter('generated.d.ts'));
    }
}
