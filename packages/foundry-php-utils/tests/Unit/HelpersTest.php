<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

use function Foundry\Helpers\arrayBool;
use function Foundry\Helpers\arrayInt;
use function Foundry\Helpers\arrayNullableString;
use function Foundry\Helpers\arrayString;
use function Foundry\Helpers\assertInstanceOf;
use function Foundry\Helpers\assertString;
use function Foundry\Helpers\cacheArray;
use function Foundry\Helpers\cacheInt;
use function Foundry\Helpers\cacheString;
use function Foundry\Helpers\configArray;
use function Foundry\Helpers\configBool;
use function Foundry\Helpers\configInt;
use function Foundry\Helpers\configNullableString;
use function Foundry\Helpers\configString;
use function Foundry\Helpers\fileModificationTime;
use function Foundry\Helpers\stringList;
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

it('returns config value as bool when it is a native bool', function (): void {
  Config::set('flag.true', true);
  Config::set('flag.false', false);

  expect(configBool('flag.true'))->toBeTrue();
  expect(configBool('flag.false'))->toBeFalse();
});

it('returns default when config value is not a native bool', function (): void {
  Config::set('flag.string', 'true');
  Config::set('flag.int', 1);

  expect(configBool('flag.string', true))->toBeTrue();
  expect(configBool('flag.int'))->toBeFalse();
});

it('returns array offset as non-empty string', function (): void {
  expect(arrayString(['name' => 'core', 'empty' => ''], 'name'))->toBe('core');
});

it('returns default when array offset is missing or empty', function (): void {
  expect(arrayString(['empty' => ''], 'empty', 'fallback'))->toBe('fallback');
  expect(arrayString([], 'missing', 'fallback'))->toBe('fallback');
  expect(arrayString(['n' => 5], 'n', 'fallback'))->toBe('fallback');
});

it('returns array offset as nullable string', function (): void {
  expect(arrayNullableString(['name' => 'core'], 'name'))->toBe('core');
  expect(arrayNullableString(['empty' => ''], 'empty'))->toBeNull();
  expect(arrayNullableString([], 'missing'))->toBeNull();
  expect(arrayNullableString(['n' => 5], 'n'))->toBeNull();
});

it('returns array offset as int when numeric', function (): void {
  expect(arrayInt(['ttl' => 300], 'ttl'))->toBe(300);
  expect(arrayInt(['ttl' => '300'], 'ttl'))->toBe(300);
  expect(arrayInt([], 'missing', 99))->toBe(99);
  expect(arrayInt(['n' => 'abc'], 'n', 7))->toBe(7);
});

it('returns array offset as bool when native bool', function (): void {
  expect(arrayBool(['flag' => true], 'flag'))->toBeTrue();
  expect(arrayBool(['flag' => '1'], 'flag', true))->toBeTrue();
  expect(arrayBool([], 'missing', false))->toBeFalse();
});

it('filters a mixed value into a list of strings', function (): void {
  expect(stringList(['a', 'b', 3, null, 'c']))->toBe(['a', 'b', 'c']);
});

it('returns empty list when value is not an array', function (): void {
  expect(stringList('not-an-array'))->toBe([]);
  expect(stringList(null))->toBe([]);
});

it('asserts and returns a string value', function (): void {
  expect(assertString('core'))->toBe('core');
});

it('throws when assertString receives a non-string', function (): void {
  expect(fn () => assertString(42))->toThrow(InvalidArgumentException::class);
});

it('asserts and returns an instance of the expected class', function (): void {
  $instance = new stdClass();

  expect(assertInstanceOf($instance, stdClass::class))->toBe($instance);
});

it('throws when assertInstanceOf receives a wrong type', function (): void {
  expect(fn () => assertInstanceOf('not-an-object', stdClass::class))->toThrow(InvalidArgumentException::class);
});
