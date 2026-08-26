<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Bind Pest's Feature tests to Laravel's TestCase so that the application
| is booted and helpers like `config()`, `Cache::fake()`, etc. work.
|
*/

uses(Tests\TestCase::class)
    ->in('Feature', 'Unit');
