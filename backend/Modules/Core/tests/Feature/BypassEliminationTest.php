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

    expect($file)->not->toContain("hasRole(['ADMIN', 'DEV'");
});

it('has zero Gate::before bypass in AppServiceProvider', function (): void {
    $file = file_get_contents(
        base_path('app/Providers/AppServiceProvider.php')
    );

    expect($file)->not->toContain('Gate::before')
        ->and($file)->not->toContain("hasRole('ADMIN')")
        ->and($file)->not->toContain("hasRole('DEV')");
});

it('has zero hasRole bypass in EditStaffUserController', function (): void {
    $file = file_get_contents(
        base_path('Modules/Admin/app/Http/Controllers/StaffUsers/EditStaffUserController.php')
    );

    expect($file)->not->toContain("hasRole('ADMIN')")
        ->and($file)->not->toContain("hasRole('DEV')")
        ->and($file)->not->toContain("hasRole(['ADMIN'");
});

it('has zero hasRole bypass in AdminStaffUserService', function (): void {
    $file = file_get_contents(
        base_path('Modules/Admin/app/Services/AdminStaffUserService.php')
    );

    expect($file)->not->toContain("hasRole('ADMIN')")
        ->and($file)->not->toContain("hasRole('DEV')")
        ->and($file)->not->toContain("hasRole(['ADMIN'");
});

it('has zero hasRole bypass in RoleService', function (): void {
    $file = file_get_contents(
        base_path('Modules/Admin/app/Services/RoleService.php')
    );

    expect($file)->not->toContain("hasRole('ADMIN')")
        ->and($file)->not->toContain("hasRole('DEV')")
        ->and($file)->not->toContain("hasRole(['ADMIN'");
});

it('has zero hasRole bypass in StaffUserRequest', function (): void {
    $file = file_get_contents(
        base_path('Modules/Admin/app/Http/Requests/StaffUserRequest.php')
    );

    expect($file)->not->toContain("hasRole('ADMIN')")
        ->and($file)->not->toContain("hasRole('DEV')")
        ->and($file)->not->toContain("hasRole(['ADMIN'");
});
