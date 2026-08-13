<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Fakes;

/**
 * Factory for permission-checker closures used across menu/navigation tests.
 *
 * - `allow()` returns a closure that always grants access.
 * - `deny($perm)` returns a closure that denies only the specified permission.
 */
final class FakePermissionChecker
{
    /** @return callable(string): bool */
    public static function allow(): callable
    {
        return static fn (string $perm): bool => true;
    }

    /** @return callable(string): bool */
    public static function deny(string $deniedPerm): callable
    {
        return static fn (string $perm): bool => $perm !== $deniedPerm;
    }
}
