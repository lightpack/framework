# Contributing to Lightpack Framework

Thank you for your interest in contributing to Lightpack Framework! This document provides guidelines and instructions for contributing.

## Table of Contents

- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Submitting Changes](#submitting-changes)
- [Running Tests](#running-tests)
- [Static Analysis](#static-analysis)
- [Code Style](#code-style)
- [Branch Naming](#branch-naming)
- [Commit Messages](#commit-messages)
- [Reporting Issues](#reporting-issues)

## Getting Started

1. Fork the repository on GitHub
2. Clone your fork locally
3. Create a new branch for your contribution
4. Make your changes
5. Run tests and ensure everything passes
6. Submit a pull request

## Development Setup

### Requirements

- PHP >= 8.2
- Composer
- MySQL (for integration tests)
- Redis (optional, for Redis driver tests)

### Installation

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/framework.git
cd framework

# Install dependencies
composer install

# Verify setup
make test
```

## Coding Standards

Lightpack follows these principles:

- **PSR-4 autoloading** with namespace `Lightpack\`
- **Explicit over implicit** - no magic where clarity is better
- **Single responsibility** - each class should have one clear purpose
- **Type safety** - use type declarations everywhere
- **PHPDoc** - document public APIs with `@param`, `@return`, `@throws`

### Key Patterns

- Use `src/Framework` for all framework code
- Tests go in `tests/` mirroring the source structure
- Configuration files go in `test-config/` or framework-level config
- Never assume Laravel patterns - this is Lightpack with its own architecture

## Submitting Changes

### Branch Naming

Use descriptive branch names with the following prefixes:

- `feature/` - New features or enhancements
- `fix/` - Bug fixes
- `docs/` - Documentation changes
- `refactor/` - Code refactoring without functional changes
- `test/` - Test additions or improvements
- `chore/` - Maintenance tasks (dependencies, tooling)

Examples:
```
feature/ai-streaming-support
fix/route-middleware-order
docs/cache-configuration-guide
```

### Commit Messages

Follow the [Conventional Commits](https://www.conventionalcommits.org/) specification:

```
<type>(<scope>): <description>

[optional body]

[optional footer(s)]
```

Types:
- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation only
- `style:` - Code style changes (formatting, semicolons, etc.)
- `refactor:` - Code refactoring
- `test:` - Adding or updating tests
- `chore:` - Build process, dependencies, tooling

Examples:
```
feat(cache): add Redis cache driver
fix(routing): correct middleware execution order
docs: update installation instructions for PHP 8.3
test(database): add coverage for nested transactions
```

## Running Tests

### All Tests (Recommended)

The framework uses process-isolated test suite runs for reliability:

```bash
composer test
# or
./run-tests.sh
```

This runs each test suite individually to prevent cross-suite state contamination.

### Specific Test Suite

```bash
./vendor/bin/phpunit --testsuite Database
./vendor/bin/phpunit --testsuite Cache
```

### Quick Test Run (Single suite)

```bash
./vendor/bin/phpunit --testsuite Cache
```

### With Coverage

```bash
./vendor/bin/phpunit --coverage-html coverage/
```

### Using the Test Runner Script

```bash
# Run all suites with summary
./run-tests.sh

# Run all suites with full output
./run-tests.sh --verbose
```

### Integration Tests

Integration tests require external API keys (OpenAI, AWS, Mailtrap, etc.) and are **not run on PRs**.

- **For contributors**: Unit tests via `run-tests.sh` are sufficient.
- **For maintainers**: Run integration tests locally before merging significant changes or releases:
  ```bash
  ./vendor/bin/phpunit --testsuite Integration
  ./vendor/bin/phpunit --testsuite Http
  ```
- **Nightly CI**: Integration tests run automatically every night at 2 AM UTC via GitHub Actions.

## Static Analysis

We use PHPStan for static analysis. Run it with:

```bash
composer stan
# or
vendor/bin/phpstan analyse --no-progress
```

## Code Style

We use PHP-CS-Fixer to enforce a consistent code style.

### Check Style (CI mode)

```bash
composer cs
# or
vendor/bin/php-cs-fixer fix --dry-run --diff --verbose
```

### Fix Style Automatically

```bash
composer cs:fix
# or
vendor/bin/php-cs-fixer fix --verbose
```

## Reporting Issues

Before opening an issue:

1. Check if the issue already exists
2. Use the appropriate issue template:
   - **Bug Report** - For bugs and unexpected behavior
   - **Feature Request** - For new features or enhancements
3. Provide as much detail as possible:
   - Lightpack version
   - PHP version
   - Steps to reproduce
   - Expected vs actual behavior
   - Error logs or stack traces

## Security

If you discover a security vulnerability, please do **not** open a public issue or discussion. See our [Security Policy](SECURITY.md) for responsible disclosure instructions.

## Pull Request Process

1. Ensure your branch is up to date with `main`
2. All tests must pass (`make test`)
3. Static analysis must pass (`make stan`)
4. Code style must pass (`make cs`) - or run `make fix` to auto-fix
5. Update relevant documentation (README, CHANGELOG, etc.)
6. Fill out the PR template completely
7. Request review from maintainers

## Code of Conduct

All contributors are expected to follow our [Code of Conduct](CODE_OF_CONDUCT.md).

## Questions?

If you have questions that aren't answered here:

- Open a [GitHub Discussion](https://github.com/lightpack/framework/discussions)
- Email: pt21388@gmail.com

Thank you for contributing to Lightpack Framework!
