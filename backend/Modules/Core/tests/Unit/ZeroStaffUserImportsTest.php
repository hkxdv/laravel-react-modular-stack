<?php

declare(strict_types=1);

// ── REQ-A1, REQ-A2, REQ-A12: Zero StaffUser imports in Application/Contracts layers ──
// Pre-existing coupling allowed by design D4: ComposeInertiaProps and AssembleMenu
// keep StaffUser imports for instanceof StaffUser guard (Fase 3 moves StaffUserResource).
// SyncCrossGuardPermissions uses StaffUser::syncPermissionsBetweenGuards() static call.

it('has zero StaffUser imports in Application layer (excluding pre-existing coupling)', function (): void {
    $allowedFiles = [
        'ComposeInertiaProps.php',
        'AssembleMenu.php',
        'SyncCrossGuardPermissions.php',
    ];

    /** @var list<string> $files */
    $files = glob(base_path('Modules/Core/src/Application/**/*.php')) ?: [];
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
    /** @var list<string> $files */
    $files = glob(base_path('Modules/Core/src/Contracts/**/*.php')) ?: [];
    foreach ($files as $file) {
        $content = file_get_contents($file);
        expect($content)
            ->not->toContain('use Modules\\Core\\Infrastructure\\Eloquent\\Models\\StaffUser');
    }
})->expect('REQ-A2');

it('has no Admin imports in Core', function (): void {
    /** @var list<string> $files */
    $files = glob(base_path('Modules/Core/src/**/*.php'), 8192) ?: [];
    foreach ($files as $file) {
        $content = file_get_contents($file);
        expect($content)
            ->not->toContain('Modules\\Admin\\');
    }
})->expect('REQ-A12');
