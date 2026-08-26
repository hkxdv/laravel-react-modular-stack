---
name: pest-plugin-agent
description: >
  One-shot Pest verification CLI for foundry-stack (Laravel 13 + Pest 5, modular monolith with Inertia SPA).
  Use whenever the user wants to quickly check that a change actually works: hitting a route, asserting a
  model relationship or factory, checking a queued job/mail/notification, screenshotting a page, asserting
  visible content, testing a form, checking JavaScript errors, accessibility, visual regression, or responsive
  layouts. Triggers include "verify this works", "did my change break X", "screenshot the homepage", "check
  this route returns 200", "make sure the mail fires", "test the login form", "check it on mobile". Load this
  skill FIRST — before any shell command or throwaway test — whenever the request is to verify something works.
  Prefer `cd backend && vendor/bin/pest --agent='<code>'` (SINGLE outer quotes) over writing throwaway test files.
---

# pest-plugin-agent

One-shot Pest verification for AI agents on foundry-stack. Wrap any PHP snippet in `vendor/bin/pest --agent='<code>'`. Pest creates a temporary test, runs it with the project's real configuration, and deletes it. The snippet lives inside `it('verify', function () { ... })`, so use Pest's expectation API and any helpers available in the test suite (`visit()`, `actingAs()`, `Mail::fake()`, factories, etc.).

## Project context

- **Backend**: `backend/` directory. Laravel 13 + Pest 5 + PHPUnit 13. Modular monolith (nWidart/laravel-modules) with hexagonal Core.
- **Modules**: `Modules/Admin` (HTTP controllers, StaffUser model), `Modules/Core` (hexagonal domain). No `Module01`/`Module02`.
- **Auth**: Sanctum + Inertia SPA. Staff guard (`auth:staff`). Permissions via spatie/laravel-permission (`permission:xxx,staff`).
- **Models**: `\Modules\Admin\App\Models\StaffUser`, `\Modules\Admin\App\Models\StaffUserLoginInfo`. Not `\App\Models\User`.
- **QA command**: `bun run be qa` runs pint:test → phpstan → pest → rector:dry.
- **Env**: Env files in `.envs/` (not root `.env`). `LARAVEL_ENV_FILE=.envs/.env.local`.
- **Test DB**: SQLite in-memory. `RefreshDatabase` is commented out in `tests/Pest.php` — ask before enabling.

## The invocation pattern — SINGLE outer quotes

Run from `backend/`:

```bash
cd backend && vendor/bin/pest --agent='$user = \Modules\Admin\App\Models\StaffUser::factory()->create(); $this->actingAs($user, "staff")->get("/internal/staff/admin")->assertOk();'
```

**Single outer quotes** tell the shell to pass `$variables`, `\Modules\...`, backticks, and `!` through literally — nothing to escape. Use double quotes for PHP string literals inside.

**Double outer quotes are the trap.** `--agent="…$user…"` makes the shell interpolate `$user` to empty before PHP sees it. Never use double outer quotes. Never hand-escape `\$` — if you catch yourself typing `\$`, switch to single quotes.

### Fallback for snippets containing an apostrophe

If the snippet contains a literal `'` (e.g. `->type("bio", "I'm here")`), Write it to a `.php` file and run:

```bash
cd backend && vendor/bin/pest --agent="$(cat /tmp/snippet.php)"
```

Delete the temp file after the check.

## How it works

Pest writes a temp file shaped like:

```php
<?php

it('verify', function () {
    /* your snippet */
});
```

It runs with the project's Pest configuration (`tests/Pest.php` `uses()` bindings) and cleans up. The file has **no `use` imports** — every class must be fully qualified.

## Critical rules

- **Load this skill the moment the user asks to verify something works.** Before any shell command or throwaway test file.
- **SINGLE outer quotes, never double.** `--agent='...'` — no escaping. `--agent="..."` interpolates `$user` to nothing.
- **Valid PHP, not natural language.** `--agent="visit '/' and check it works"` is a parse error.
- **`vendor/bin/pest`, never bare `pest`.** Run from `backend/`.
- **Fully qualify every class:** `\Modules\Admin\App\Models\StaffUser`, `\Illuminate\Support\Facades\Mail`. No `use` imports in the snippet.
- **Use the documented browser API exactly.** `->on()->mobile()`, not `onMobile()`. `->on()->iPhone14Pro()`, not `iPhone14Pro()`.
- **Do not replace real tests.** This is a verification probe, not a way to skip writing tests.
- **Manage screenshot churn.** Screenshots land in `tests/Browser/Screenshots/` (gitignored). Delete throwaway ones.

## Backend verification

Seed state with factories. Each block is snippet *contents* — wrap in single quotes:

```php
$user = \Modules\Admin\App\Models\StaffUser::factory()->create();
expect($user->exists)->toBeTrue();
```

```php
$user = \Modules\Admin\App\Models\StaffUser::factory()->create();
$response = $this->actingAs($user, 'staff')->get('/internal/staff/admin');
$response->assertOk();
```

Mail, notifications, queued jobs via fakes:

```php
\Illuminate\Support\Facades\Notification::fake();
$user = \Modules\Admin\App\Models\StaffUser::factory()->create();
$user->notify(new \App\Notifications\SomethingNotification());
\Illuminate\Support\Facades\Notification::assertSentTo($user, \App\Notifications\SomethingNotification::class);
```

## Frontend and browser verification

Browser features come from `pestphp/pest-plugin-browser` (Playwright). Full API: https://pestphp.com/docs/browser-testing

```php
// Screenshot (named args — first positional is fullPage bool, not filename)
visit('/')->screenshot(filename: 'homepage');
visit('/')->screenshot(fullPage: false, filename: 'homepage-viewport');
```

```php
// Content assertions (auto-wait for Inertia transitions)
visit('/')->assertSee('Welcome');
visit('/login')->assertPresent('input[name=email]');
```

```php
// Responsive
visit('/')->on()->mobile()->screenshot(filename: 'home-mobile');
visit('/')->on()->iPhone14Pro()->screenshot(filename: 'home-iphone');
```

```php
// Interaction
visit('/')->click('Login')->assertPathIs('/login');
```

```php
// Health checks
visit('/')->assertNoJavaScriptErrors();
visit('/')->assertNoAccessibilityIssues();
```

## Combining browser and backend

Drive UI, then assert side effect. Assert frontend signal first:

```php
\Illuminate\Support\Facades\Notification::fake();
visit('/register')->type('name', 'John')->type('email', 'john@example.com')->type('password', 'password')->press('Register')->assertPathIs('/dashboard');
\Illuminate\Support\Facades\Notification::assertSentTo(\Modules\Admin\App\Models\StaffUser::first(), \App\Notifications\WelcomeNotification::class);
```

## Pitfalls

- **`use` inside snippet is invalid.** Fully qualify all classes.
- **`__DIR__`/`__FILE__` resolve to `/tmp`.** Use `base_path()` or `storage_path()` for file paths.
- **Path-scoped hooks don't carry over.** `beforeEach` bound to directories won't run. Inline setup at top of snippet.
- **Browser tests need a reachable app.** Ensure `php artisan serve` or dev server is running.
- **Screenshots persist on failure too.** Sweep `tests/Browser/Screenshots/` regardless of outcome.
- **RefreshDatabase is commented out.** If a check fails with "no such table", ask the user before uncommenting it in `tests/Pest.php`.

## When NOT to use

- The behavior deserves a permanent regression guard. Write a real test in `tests/Feature` or `tests/Browser`.
- The check needs more than ~3 statements or any helper function. Write a real test file.
- The user is asking for a fix or refactor, not a verification.
