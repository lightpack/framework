# Changelog

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