# Changelog

## [0.10.0] - 2026-06-22

### Features

- **Deploy Suite**. Lightpack now ships with everything you need to go from a fresh Ubuntu server to a running application right from your local development machine. Provision the server once, then deploy, rollback, and manage your app from the console on your laptop.
- You get: one-command server setup (nginx, PHP-FPM, MySQL, Redis, firewall, SSL), deployments that pull code, sync secrets, run migrations, and reload services, instant rollbacks to any previous commit, remote database backups and restores, queue workers managed via Supervisor, cron scheduling, and the ability to run arbitrary commands on your server.

## [0.9.8] - 2026-05-26

### Fixes & Refactors

- **ValidationException**: Added `setResponse()` / `getResponse()` to carry a prepared `Response` through the exception boundary.
- **FormRequest / API Validation**: JSON validation failure path now builds a `Response` via the `response()` service (instead of `redirect()`) and carries it through `ValidationException`.
- **Dispatcher**: Returns the `Response` carried by `ValidationException` when present, falling back to `redirect()` for web contexts.

## [0.9.7] - 2026-05-26

### Fixes & Refactors

- **FormRequest / Container**: Fixed container self-resolution bug in `__boot()` by using `Container::getInstance()` instead of DI-injected `Container`, preventing "Service `redirect` is not registered" errors during API validation.
- **FormRequest / API Validation**: Fixed JSON validation failure path to throw `ValidationException` instead of returning, ensuring API endpoints correctly return `422 Unprocessable Entity`.
- **FormRequest**: Improved PHPDoc for `data()`, `beforeSend()`, and `beforeRedirect()` hooks.

## [0.9.6] - 2026-05-25

### Features

- **Process::spawn()**: Added support to spawn child processes. Streams output directly to the terminal (no buffering). Supports custom working directory, environment variables, and signal-safe termination.
- **WatchesEnvTrait**: Monitors `.env` file changes and gracefully restarts the child process. Used by `ServeCommand` and `ProcessJobs`.

## [0.9.5] - 2026-05-22

### Features

- **Route Model Binding**: Automatic dependency injection for route parameters. Group-level binding with inheritance and override support.
- **Moment**: Added methods for date comparison and boundary calculations and improved datetime handling.

## [0.9.4] - 2026-05-20

### Fixes & Refactors

- **Schema / Foreign Keys**: Auto-generated constraint names (`fk_{table}_{column}`) for predictable `dropForeign()` calls. Override via `->name('custom_name')`.
- **Query Builder**: `update()` and `delete()` now require an explicit `WHERE` clause for safeguard. Prevents accidental full-table mutations.

## [0.9.3] - 2026-05-20

### Features

- **Limited eager loading** for `hasMany` and `hasManyThrough` relations using `ROW_NUMBER()` window functions (`limit()` constraints in eager loads now apply per-parent).
- **Relation aggregates**: `withSum()`, `withAvg()`, `withMin()`, `withMax()`, `withCount()` with correlated subquery support for `orderBy()`.
- **Collection** additions: `last()`, `sort()`.
- **Arr utils**: `flatten()`, `groupBy()`, `sort()`.
- **ServeCommand**: port validation, availability checking, and ASCII art banner.
- **ProcessJobs**: ASCII art banner on startup.
- **Pagination**: `isEmpty()` and `isNotEmpty()` methods.

### Fixes & Refactors

- **HTTP**: Set `Content-Type` header when `setType()` is called.
- **Debug**: Use relevant trace file/line in exception renderer.
- **Tests**: Reset container instance after each test in `tearDown()`.
- **Query**: Make aggregate `groupBy()` methods chainable; standardize result column naming.
- **Lucid**: Allow `null` eager-loaded relations in strict mode without throwing exceptions.
- **Console**: Standardize spacing in `ServeCommand`; fix label padding; remove redundant newlines in create commands; add newline after each migration output.

## [0.9.2] - 2026-05-15

- Fix: Add foreign key to existing table via `alterTable()->add()`.

## [0.9.1] - 2026-05-14

- Fix: Standardize spacing in conditional statement for auth config check.

## [0.9.0] - 2026-05-14

- Dropped alpha tag.
- CI (8.2–8.5), PHP-CS-Fixer, open source docs.
- Fixed: cross-platform exit codes, flaky job tests, PHP 8.5+ OpenSSL compat.