<?php

declare(strict_types=1);

// ── AUTC-S7: Zero 'staff'/'tenant' literals in Core Application/Contracts ──
// Escanea string literals ('staff'/'tenant') vía token_get_all en las capas
// Application + Contracts. Config/, Infrastructure/ y shell quedan exentas.
//
// Whitelist por diseño (D5):
//  - SyncCrossGuardPermissions.php (Application): default de sync_excludes
//    ['staff'] — PERMANENTE, default opinado y justificado del caso de uso.
//  - ComposeInertiaProps.php desapareció del whitelist en S2: el registry
//    (AuthUserPresenterRegistry) reemplazó la enumeración de guards línea 44.

/** @return list<string> */
function coreSrcPhpFilesForGuardLiterals(string $subPath = ''): array
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

/**
 * Recoge los literales string ('staff'/'tenant') de un archivo PHP.
 *
 * @return list<string> Literales encontrados (sin repeticiones)
 */
function guardLiteralsInFile(string $file): array
{
    $tokens = token_get_all((string) file_get_contents($file));
    $literals = [];

    foreach ($tokens as $token) {
        if (! is_array($token)) {
            continue;
        }

        if ($token[0] === T_CONSTANT_ENCAPSED_STRING) {
            $value = mb_trim($token[1], "'\"");
            if ($value === 'staff' || $value === 'tenant') {
                $literals[] = $value;
            }
        }
    }

    return array_values(array_unique($literals));
}

it('has zero staff/tenant string literals in Application layer (excluding whitelist)', function (): void {
    // @see design D5: SyncCrossGuardPermissions (PERMANENT, sync_excludes
    // opinionated default). ComposeInertiaProps salió del whitelist en S2.
    $applicationWhitelist = [
        'SyncCrossGuardPermissions.php', // permanente: sync_excludes default ['staff']
    ];

    $files = coreSrcPhpFilesForGuardLiterals('Application');
    foreach ($files as $file) {
        $basename = basename($file);
        if (in_array($basename, $applicationWhitelist, true)) {
            continue;
        }

        $literals = guardLiteralsInFile($file);

        expect($literals)->toBeEmpty("No esperado: literales ['"
            .implode("', '", $literals)."'] en {$basename}");
    }
})->expect('AUTC-S7');

it('has zero staff/tenant string literals in Contracts layer', function (): void {
    $files = coreSrcPhpFilesForGuardLiterals('Contracts');
    foreach ($files as $file) {
        $literals = guardLiteralsInFile($file);

        expect($literals)->toBeEmpty("No esperado: literales ['"
            .implode("', '", $literals)."'] en ".basename($file));
    }
})->expect('AUTC-S7');
