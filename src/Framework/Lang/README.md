# Lang

A lightweight, file-based translation system for Lightpack. Zero dependencies — pure PHP arrays.

## Features

- **File-based translations** — PHP array files, no external formats
- **Dot notation** — `messages.hello` maps to `lang/en/messages.php` → `hello`. Nested arrays work too: `forms.signup.title`
- **Placeholders** — `:name`, `:count` replacement
- **Pluralization** — `choice('messages.items', 5)` with pipe syntax
- **Fallback** — missing keys fall back to default locale automatically
- **Validation messages** — every built-in rule resolves its error message via `lang/{locale}/validation.php`; raw English is always the fallback

## Quick Start

### Create translation files

```php
// lang/en/messages.php
return [
    'hello'   => 'Hello',
    'welcome' => 'Welcome, :name!',
    'items'   => ':count item|:count items',
];

// lang/hi/messages.php
return [
    'hello'   => 'नमस्ते',
    'welcome' => 'स्वागत है, :name!',
];

// lang/en/forms.php
return [
    'signup' => [
        'title'  => 'Sign Up',
        'submit' => 'Create Account',
    ],
];
```

### Use in views or controllers

```php
// Simple translation
lang('messages.hello');           // 'Hello' (or 'नमस्ते' in hi)

// With placeholders
lang('messages.welcome', ['name' => 'John']);  // 'Welcome, John!'

// Pluralization
lang()->choice('messages.items', 5);  // '5 items'

// Nested arrays
lang('forms.signup.title');   // 'Sign Up' — reads lang/en/forms.php → ['signup']['title']
```

## Configuration

Lang settings live in the `lang` block inside `config/app.php`:

```php
// config/app.php
return [
    'app' => [
        // ...
        'lang' => [
            'default'   => get_env('APP_LOCALE', 'en'),
            'fallback'  => get_env('APP_FALLBACK_LOCALE', 'en'),
            'supported' => ['en', 'hi', 'es'],
            'path'      => DIR_ROOT . '/lang',
        ],
    ],
];
```

## API

| Method | Description |
|--------|-------------|
| `lang('key')` | Get translation string |
| `lang('key', ['name' => 'John'])` | Get with placeholder replacement |
| `lang()->choice('key', 5)` | Pluralized translation |
| `lang()->choice('key', 5, [], 'fr')` | Pluralized with locale override |
| `lang()->has('key')` | Check if translation exists |
| `lang()->setLocale('hi')` | Change locale manually |
| `lang()->getLocale()` | Get current locale |
| `lang()->setLocaleRule('xx', fn($n) => ...)` | Register custom plural rule |

## Validation Messages

Every built-in validation rule carries a lang key of the form `validation.{rule}`. When the current locale has a `validation.php` file, messages are read from it automatically. When the file is absent, the raw English string hard-coded in each rule is used — **no setup is required**.

### Create `lang/en/validation.php`

```php
// lang/en/validation.php
return [
    'required'          => 'This field is required',
    'email'             => 'Must be a valid email address',
    'min'               => 'Must not be less than :min',
    'max'               => 'Must not be greater than :max',
    'between'           => 'Must be between :min and :max',
    // ... add only the keys you want to override
];
```

Create the equivalent file for every locale you support (`hi`, `fr`, etc.) with translated strings.

### Available keys and placeholders

See `src/Framework/Lang/stubs/validation.stub.php` — copy it to `lang/{locale}/validation.php` for a complete, commented list of every key with its default message and placeholders. Override only the keys you need.

Or use the console command:

```bash
php console create:lang --support=validation
```

This copies the stub directly into `lang/en/validation.php` (use `--locale=fr` for a different locale, `--force` to overwrite).

> **Note:** `FileRule` (PHP upload error codes) is the only rule that does not use lang keys. Its messages are system-level PHP error descriptions. The message can still be overridden with `setMessage()` directly on the rule object.

### Example — French validation

```php
// lang/fr/validation.php
return [
    'required'   => 'Ce champ est obligatoire',
    'email'      => 'Adresse e-mail invalide',
    'min'        => 'La valeur minimale est :min',
    'max'        => 'La valeur maximale est :max',
    'file_size'  => 'Le fichier ne doit pas dépasser :size',
];
```

Set `fr` as the active locale via `lang()->setLocale('fr')` and all validation errors are automatically in French.

## Pluralization Syntax

### Simple (English-style singular/plural)

```php
'items' => ':count item|:count items',
```

- `choice('items', 1)` → `1 item`
- `choice('items', 5)` → `5 items`

### Indexed (Arabic, Russian, Polish, etc.)

For languages with more than two plural forms, prefix each form with `{index}`:

```php
// Russian — 3 forms
'articles' => '{0} :count статей|{1} :count статья|{2} :count статьи',

// Arabic — 6 forms (dual for exactly 2, unique to Arabic)
'articles' => '{0} no articles|{1} one article|{2} two articles|{3} :count articles|{4} :count articles-many|{5} :count articles-other',
```

Supported locales: `en`, `es`, `fr`, `de`, `it`, `pt`, `hi`, `bn`, `nl`, `sv`, `da`, `fi`, `tr` (singular/plural), `ja`, `ko`, `zh`, `fa` (no grammatical plural), `ru`, `uk`, `cs`, `sk` (3 Slavic forms), `pl` (3 Polish forms), `ro` (3 Romanian forms), `ar` (6 forms).

Add custom rules via `lang()->setLocaleRule()`:

```php
lang()->setLocaleRule('tlh', fn($count) => $count === 1 ? 1 : 0);
```

### Where to register custom rules

Put this in an app-level provider registered in `boot/providers.php`:

```php
// app/Providers/LangCustomProvider.php
namespace App\Providers;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

class LangCustomProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        lang()->setLocaleRule('tlh', fn(int $n) => $n === 1 ? 1 : 0);
        lang()->setLocaleRule('my',  fn(int $n) => 0); // Burmese — no plural
    }
}
```

```php
// boot/providers.php
return [
    App\Providers\LangCustomProvider::class,
];
```

This runs after `LangProvider` has already registered the `lang` instance, so `lang()` is available.

## Architecture

```
src/Framework/Lang/
├── Commands/
│   └── CreateLangCommand.php  # Console command: create:lang
├── stubs/
│   └── validation.stub.php    # Full list of validation keys (copy me)
├── Lang.php              # Core translation class
├── LangProvider.php      # Service provider (container binding)
├── Pluralizer.php        # Plural form resolver (no ICU dependency)
└── README.md             # This file
```

The `Lang` class is registered as `'lang'` in the container and exposed via the `lang()` helper.

## Design Constraints

This is **intentionally** a simple system. Before reaching for more, know what it covers and what it doesn't.

### What it does well

- **Simple pluralization** — English-style `one|many` via pipe syntax
- **Complex plural forms** — Indexed `{0} form|{1} form|{2} form` for Arabic (6 forms), Russian (3), Polish (3), Romanian (3), and more
- **20+ locales built-in** — covers most common languages with correct plural rules
- **Extensible** — add any locale rule via `lang()->setLocaleRule()`
- **Zero dependencies** — no ICU, no gettext, no JSON catalogs
- **Small surface area** — learn it in 5 minutes
- **Validation messages** — all built-in validation errors are automatically translated when `lang/{locale}/validation.php` exists; raw English is always the fallback

### What it does NOT do

- **JSON or database-backed translations** — PHP arrays are fast, cacheable, and IDE-friendly. If you need real-time translation management via an admin panel, you'll need a heavier library.

This module is for teams that want to ship multilingual pages without learning a DSL or adding a dependency.
