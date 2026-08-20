<?php

declare(strict_types=1);

/**
 * Verifica que el bypass por nombre de rol ha sido eliminado completamente
 * de los 3 archivos target.
 */
it('has zero hasRole bypass in HasCrossGuardPermissions', function (): void {
    $file = file_get_contents(
        base_path('Modules/Core/src/Infrastructure/Laravel/Traits/HasCrossGuardPermissions.php')
    );

    expect($file)->not->toContain("hasRoleCross(['ADMIN', 'DEV'])");
});

it('has zero hasRole bypass in AddonRegistryService', function (): void {
    $file = file_get_contents(
        base_path('Modules/Core/src/Infrastructure/Laravel/Services/AddonRegistryService.php')
    );

    expect($file)->not->toContain("hasRole('ADMIN')")
        ->and($file)->not->toContain("hasRole('DEV')");
});

it('has zero hasRole bypass in CheckPermission', function (): void {
    $file = file_get_contents(
        base_path('app/Http/Middleware/CheckPermission.php')
    );

    expect($file)->not->toContain("hasRole(['ADMIN', 'DEV']");
});
