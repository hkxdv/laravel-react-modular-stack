<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

use function Foundry\Helpers\cacheArray;
use function Foundry\Helpers\cacheInt;
use function Foundry\Helpers\cacheString;
use function Foundry\Helpers\configArray;
use function Foundry\Helpers\configInt;
use function Foundry\Helpers\configNullableString;
use function Foundry\Helpers\configString;
use function Foundry\Helpers\fileModificationTime;
use function Foundry\Helpers\userId;

uses(Tests\TestCase::class);

it('returns config value when it is a non-empty string', function (): void {
  Config::set('core.cache.nav_cache_prefix', 'core:nav:');

  expect(configString('core.cache.nav_cache_prefix'))->toBe('core:nav:');
});

it('returns default when config key is missing', function (): void {
  expect(configString('nonexistent.key', 'fallback'))->toBe('fallback');
});

it('returns default when config value is empty string', function (): void {
  Config::set('empty.key', '');

  expect(configString('empty.key', 'fallback'))->toBe('fallback');
});

it('returns config value as int when it is numeric', function (): void {
  Config::set('core.cache.nav_assembled_ttl_seconds', 300);

  expect(configInt('core.cache.nav_assembled_ttl_seconds'))->toBe(300);
});

it('returns default when config key is missing for config_int', function (): void {
  expect(configInt('nonexistent.key', 99))->toBe(99);
});

it('returns default when config value is non-numeric for config_int', function (): void {
  Config::set('bad.key', 'abc');

  expect(configInt('bad.key', 10))->toBe(10);
});

it('returns config value when it is a non-empty string for nullable', function (): void {
  Config::set('modules.activators.file.statuses-file', '/path/to/file');

  expect(configNullableString('modules.activators.file.statuses-file'))->toBe('/path/to/file');
});

it('returns null when config key is missing for nullable', function (): void {
  expect(configNullableString('nonexistent.key'))->toBeNull();
});

it('returns config value as array', function (): void {
  Config::set('nested.array', ['a' => 1, 'b' => 2]);

  expect(configArray('nested.array'))->toBe(['a' => 1, 'b' => 2]);
});

it('returns default when config value is not an array', function (): void {
  Config::set('not.array', 'string');

  expect(configArray('not.array', ['default' => true]))->toBe(['default' => true]);
});

it('returns cached integer value', function (): void {
  Cache::put('core.nav_version', 5);

  expect(cacheInt('core.nav_version'))->toBe(5);
});

it('returns cached numeric string as int', function (): void {
  Cache::put('perm_version', '3');

  expect(cacheInt('perm_version'))->toBe(3);
});

it('returns default on cache miss', function (): void {
  expect(cacheInt('missing.key', 7))->toBe(7);
});

it('returns cached string value', function (): void {
  Cache::put('cached.string', 'hello');

  expect(cacheString('cached.string'))->toBe('hello');
});

it('returns default when cached string is empty', function (): void {
  Cache::put('empty.string', '');

  expect(cacheString('empty.string', 'fallback'))->toBe('fallback');
});

it('returns cached array value', function (): void {
  Cache::put('cached.array', ['a' => 1]);

  expect(cacheArray('cached.array'))->toBe(['a' => 1]);
});

it('returns default when cached value is not an array', function (): void {
  Cache::put('not.array', 'string');

  expect(cacheArray('not.array', ['default' => true]))->toBe(['default' => true]);
});

it('returns user auth identifier as string', function (): void {
  $user = new class implements Authenticatable {
    public function getAuthIdentifier()
    {
      return 42;
    }

    public function getAuthIdentifierName(): string
    {
      return 'id';
    }

    public function getAuthPasswordName(): string
    {
      return 'password';
    }

    public function getAuthPassword(): string
    {
      return '';
    }

    public function getRememberToken(): ?string
    {
      return null;
    }

    public function setRememberToken($value): void
    {
      //
    }

    public function getRememberTokenName(): string
    {
      return 'remember_token';
    }
  };

  expect(userId($user))->toBe('42');
});

it('returns fallback when user is null', function (): void {
  expect(userId(null))->toBe('anonymous');
});

it('returns custom fallback when user is null', function (): void {
  expect(userId(null, 'guest'))->toBe('guest');
});

it('returns file modification time', function (): void {
  $tmp = tempnam(sys_get_temp_dir(), 'test_mtime');
  file_put_contents($tmp, 'x');
  $expected = (int) filemtime($tmp);

  expect(fileModificationTime($tmp))->toBe($expected);

  unlink($tmp);
});

it('returns 0 for missing file', function (): void {
  expect(fileModificationTime('/nonexistent/path'))->toBe(0);
});
