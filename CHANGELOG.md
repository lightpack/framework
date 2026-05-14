# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Open source readiness: CI/CD, issue templates, contribution guidelines, code of conduct, security policy.
- GitHub Actions workflow for automated testing across PHP 8.2 and 8.3.
- PHPStan static analysis integration (level 5).
- PHP-CS-Fixer automated code style enforcement.
- Makefile for standardized development commands.

### Changed
- `composer.json` type changed from `project` to `library` for proper Packagist distribution.
- Updated `.gitattributes` and `.gitignore` for cleaner archive exports.

## [0.9.0-alpha] - 2025-05-XX

### Added
- AI integration module with support for OpenAI, Anthropic, Gemini, Groq, and Mistral providers.
- Real-time Cable system with database and Redis drivers, presence channels, and client-side library.
- Redis support for cache, sessions, and job queues.
- File upload system with local and S3 storage, image transformations, and model trait integration.
- RBAC (Role-Based Access Control) with permissions, roles, and user-role assignments.
- Audit logging for database change tracking.
- Webhook system with signature verification and event dispatching.
- Multi-tenancy support with tenant context and scope filtering.
- Social authentication (OAuth2) with Google and GitHub providers.
- Two-factor authentication (2FA) with TOTP and SMS support.
- PDF generation integration with DOMPdf.
- Mail system with SMTP, Mailtrap, and Resend drivers.
- SMS integration with Twilio.
- Job queue system with database and Redis engines.
- Task scheduling with cron-like expressions.
- Settings management with database-backed configuration.
- Taxonomy and tagging system.
- Secrets management with encrypted storage.
- Factory pattern for test data generation with Faker integration.
- QueryFilter for request-based database filtering.
- Limiter utility for rate limiting.
- Captcha system with native and refreshable drivers.
- Enhanced debugging system with development panel.
- Custom ORM cast classes for domain-specific type handling.
- Daily file logger with automatic log rotation and cleanup.

### Changed
- Refactored Model class into dedicated AttributeHandler and RelationHandler for better separation of concerns.
- Nested transaction support using transaction counter approach.
- Automatic column name escaping in Schema Builder for MySQL reserved words.
- Enhanced Cable.js with sound notifications and scaling documentation.

### Fixed
- Upload model path field handling to prevent SQL errors on missing required fields.
- Various bug fixes across routing, validation, and database layers.
