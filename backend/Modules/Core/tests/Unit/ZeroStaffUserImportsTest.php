<?php

declare(strict_types=1);

// ── REQ-A1, REQ-A2, REQ-A12: Zero StaffUser imports in Application/Contracts layers ──
// Pre-existing coupling allowed by design D4: ComposeInertiaProps and AssembleMenu
// keep StaffUser imports for instanceof StaffUser guard (Fase 3 moves StaffUserResource).
// SyncCrossGuardPermissions uses StaffUser::syncPermissionsBetweenGuards() static call.

/** @return list<string> */
function coreSrcPhpFiles(string $subPath = ''): array
{
    $dir = base_path("Modules/Core/src/{$subPath}");
    if (! is_dir($dir)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

it('has zero StaffUser imports in Application layer (excluding pre-existing coupling)', function (): void {
    $allowedFiles = [
        'ComposeInertiaProps.php',
        'AssembleMenu.php',
        'SyncCrossGuardPermissions.php',
    ];

    $files = coreSrcPhpFiles('Application');
    foreach ($files as $file) {
        $basename = basename($file);
        if (in_array($basename, $allowedFiles, true)) {
            continue;
        }

        $content = file_get_contents($file);
        expect($content)
            ->not->toContain('use Modules\\Core\\Infrastructure\\Eloquent\\Models\\StaffUser');
    }
})->expect('REQ-A1');

it('has zero StaffUser imports in Contracts layer', function (): void {
    $files = coreSrcPhpFiles('Contracts');
    foreach ($files as $file) {
        $content = file_get_contents($file);
        expect($content)
            ->not->toContain('use Modules\\Core\\Infrastructure\\Eloquent\\Models\\StaffUser');
    }
})->expect('REQ-A2');

it('has no Admin imports in Core', function (): void {
    // AuthPageProps (Application/View) usa la unión StaffUserDto|TenantUserDto
    // con DTOs relocalizados a módulos — TRANSITORIO S2: S3 (AUTC-S3) amplía
    // el tipo a UserDto|null y elimina estos imports.
    $allowedFiles = [
        'AuthPageProps.php',
    ];

    $files = coreSrcPhpFiles();
    foreach ($files as $file) {
        if (in_array(basename($file), $allowedFiles, true)) {
            continue;
        }

        $content = file_get_contents($file);
        expect($content)
            ->not->toContain('Modules\\Admin\\');
    }
})->expect('REQ-A12');
