<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Providers\SessionServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\SQLiteConnection;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests for CustomDatabaseSessionHandler's resolveMorphClass behavior.
 *
 * Verifies D1 (guard-to-morph alias convention) works for any guard,
 * not just hardcoded 'staff'.
 */
final class SessionMorphClassTest extends TestCase
{
    private Container $container;

    private $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();

        // Bind config repository with session config
        $config = new Repository([
            'session.table' => 'sessions',
            'session.lifetime' => 120,
            'session.connection' => null,
        ]);
        $this->container->instance(Repository::class, $config);

        // Create real PDO connection to SQLite in-memory
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create sessions table
        $pdo->exec('CREATE TABLE sessions (
            id TEXT PRIMARY KEY,
            authenticatable_type TEXT,
            authenticatable_id INTEGER,
            ip_address TEXT,
            user_agent TEXT,
            payload TEXT,
            last_activity INTEGER
        )');

        // Create a real SQLiteConnection wrapping the PDO
        $connection = new SQLiteConnection($pdo);

        // CustomDatabaseSessionHandler is defined in SessionServiceProvider.php (same file)
        require_once dirname(__DIR__, 2).'/app/Providers/SessionServiceProvider.php';

        // Instantiate our CustomDatabaseSessionHandler, not the base one
        $this->handler = new \App\Providers\CustomDatabaseSessionHandler(
            $connection,
            'sessions',
            120,
            $this->container,
        );
    }

    /**
     * Test that resolveMorphClass returns '{guard}-user' for any guard name.
     */
    public function test_resolve_morph_class_returns_guard_user_convention(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('resolveMorphClass');

        // Staff guard
        $result = $method->invoke($this->handler, 'staff');
        $this->assertSame('staff-user', $result);

        // Tenant guard
        $result = $method->invoke($this->handler, 'tenant');
        $this->assertSame('tenant-user', $result);

        // Arbitrary guard
        $result = $method->invoke($this->handler, 'admin');
        $this->assertSame('admin-user', $result);
    }

    /**
     * Test that resolveMorphClass handles edge case: guard name with special chars.
     */
    public function test_resolve_morph_class_preserves_guard_name_format(): void
    {
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('resolveMorphClass');

        // Guard with hyphen
        $result = $method->invoke($this->handler, 'my-guard');
        $this->assertSame('my-guard-user', $result);

        // Guard with underscore
        $result = $method->invoke($this->handler, 'my_guard');
        $this->assertSame('my_guard-user', $result);
    }
}
