# Lightpack Framework

⚡ **Lightpack** is a lightweight, modern PHP web framework designed for developers who value simplicity, explicitness, and clean architecture.

<p align="center">
<a href="https://github.com/lightpack/framework/actions"><img src="https://github.com/lightpack/framework/workflows/CI/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/lightpack/framework"><img src="https://img.shields.io/packagist/php-v/lightpack/framework" alt="PHP Version"></a>
<a href="https://packagist.org/packages/lightpack/framework"><img src="https://img.shields.io/packagist/v/lightpack/framework" alt="Latest Release"></a>
<a href="https://packagist.org/packages/lightpack/framework"><img src="https://img.shields.io/packagist/l/lightpack/framework" alt="License"></a>
</p>

## Philosophy

- **Explicit over implicit** — No magic. You see what you get.
- **Lightweight core** — Only what you need, extensible via optional packages.
- **Clean architecture** — Separation of concerns, testable by design.
- **Developer-friendly** — Simple APIs, clear documentation, no steep learning curve.

## Features

- **MVC Architecture** — Clean separation with Controllers, Models (Lucid ORM), and Views.
- **Lucid ORM** — Lightweight ORM with relationships, casts, eager loading, and custom cast classes.
- **Routing** — Expressive routing with middleware, filters, and named routes.
- **Database** — Query builder, schema builder, migrations, and nested transactions.
- **Real-Time** — Cable system for event-driven communication with database/Redis drivers and presence channels.
- **AI Integration** — Unified API for OpenAI, Anthropic, Gemini, Groq, and Mistral.
- **Auth & Security** — Session-based auth, RBAC, 2FA/TOTP, social login, and CSRF protection.
- **File Uploads** — Multi-disk storage (local/S3), image transformations, and model trait integration.
- **Mail & SMS** — Multiple mail drivers (SMTP, Resend) and SMS (Twilio).
- **Job Queues** — Background jobs with database and Redis engines.
- **Task Scheduling** — Cron-like task scheduling.
- **Caching** — Multiple drivers (file, database, Redis) with TTL preservation.
- **Testing** — Built-in testing utilities with PHPUnit integration, database transactions, and mail assertions.
- **And more** — Validation, PDF generation, webhooks, audit logging, multi-tenancy, settings, taxonomies, secrets, captcha, rate limiting.

## Requirements

- PHP >= 8.2
- Composer
- MySQL (for database features)
- Redis (optional, for high-performance cache/sessions/queues)

## Installation

```bash
composer create-project lightpack/framework my-app
cd my-app
```

Or add to an existing project:

```bash
composer require lightpack/framework
```

## Quick Start

```php
<?php
// routes/web.php
$router->get('/', function () {
    return 'Hello, Lightpack!';
});

// app/Controllers/HomeController.php
namespace App\Controllers;

class HomeController
{
    public function index()
    {
        return view('home', ['name' => 'Lightpack']);
    }
}
```

## Documentation

Full documentation is available at [lightpack.github.io](https://lightpack.github.io).

## Contributing

We welcome contributions! Please read our [Contributing Guide](CONTRIBUTING.md) for details on:

- Setting up your development environment
- Coding standards and conventions
- Running tests and static analysis
- Submitting pull requests

## Community

- [GitHub Discussions](https://github.com/lightpack/framework/discussions) — Ask questions, share ideas
- [Issue Tracker](https://github.com/lightpack/framework/issues) — Report bugs or request features

## Security

If you discover a security vulnerability, please follow our [Security Policy](SECURITY.md) to report it responsibly.

## License

Lightpack Framework is open-sourced software licensed under the [MIT License](LICENSE).