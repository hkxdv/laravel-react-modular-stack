<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Fakes;

use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;

/**
 * Trait that creates a fake session store backed by an in-memory array
 * and binds it to a request via `setLaravelSession`.
 *
 * Use in tests that need `$request->session()` to work without a real
 * session driver (e.g. SecurityAuditService tests).
 */
/** @phpstan-ignore trait.unused */
trait SessionStoreFake
{
    private function createFakeSession(Request $request): Store
    {
        $handler = new ArraySessionHandler('test');
        $store = new Store('test', $handler, 'session_id');
        $store->start();

        $request->setLaravelSession($store);

        return $store;
    }
}
