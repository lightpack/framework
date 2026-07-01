# Lang

A lightweight, file-based translation system for Lightpack. Zero dependencies — pure PHP arrays.

## Features

- **File-based translations** — PHP array files, no external formats
- **Dot notation** — `messages.hello` maps to `app/Lang/en/messages.php` → `hello`. Nested arrays work too: `forms.signup.title`
- **Placeholders** — `:name`, `:count` replacement
- **Pluralization** — `choice('messages.items', 5)` with pipe syntax
- **Fallback** — missing keys fall back to default locale automatically
- **Auto-detection** — `SetLocaleFilter` detects locale from URL, session, or browser header

## Quick Start

### 1. Create translation files

```php
// app/Lang/en/messages.php
return [
    'hello'   => 'Hello',
    'welcome' => 'Welcome, :name!',
    'items'   => ':count item|:count items',
];

// app/Lang/hi/messages.php
return [
    'hello'   => 'नमस्ते',
    'welcome' => 'स्वागत है, :name!',
];

// app/Lang/en/forms.php
return [
    'signup' => [
        'title'  => 'Sign Up',
        'submit' => 'Create Account',
    ],
];
```

### 2. Use in views or controllers

```php
// Simple translation
lang('messages.hello');           // 'Hello' (or 'नमस्ते' in hi)

// With placeholders
lang('messages.welcome', ['name' => 'John']);  // 'Welcome, John!'

// Pluralization
lang()->choice('messages.items', 5, ['count' => 5]);  // '5 items'

// Nested arrays
lang('forms.signup.title');   // 'Sign Up' — reads app/Lang/en/forms.php → ['signup']['title']
```

### 3. Auto-detect locale via filter

Register the filter and apply it to routes:

```php
// boot/filters.php
return [
    'locale' => \Lightpack\Lang\SetLocaleFilter::class,
];

// routes.php
$route->group(['filter' => ['locale']], function($route) {
    $route->get('/about', 'AboutController@index');
});
```

Locale detection order:
1. URL path segment (`/hi/about` → `hi`)
2. Session (`session()->get('locale')`)
3. `Accept-Language` header
4. Configured default (`config('lang.default')`)

## Configuration

```php
// config/lang.php
return [
    'default'    => get_env('APP_LOCALE', 'en'),
    'fallback'   => 'en',
    'supported'  => ['en', 'hi', 'es'],
    'path'       => DIR_ROOT . '/app/Lang',
];
```

## API

| Method | Description |
|--------|-------------|
| `lang('key')` | Get translation string |
| `lang('key', ['name' => 'John'])` | Get with placeholder replacement |
| `lang()->choice('key', 5)` | Pluralized translation |
| `lang()->has('key')` | Check if translation exists |
| `lang()->setLocale('hi')` | Change locale manually |
| `lang()->getLocale()` | Get current locale |

## Pluralization Syntax

Use pipe `|` in your translation strings:

```php
'items' => ':count item|:count items',
```

- `choice('items', 1)` → `1 item`
- `choice('items', 5)` → `5 items`

## Architecture

```
src/Framework/Lang/
├── Lang.php             # Core translation class
├── LangProvider.php     # Service provider (container binding)
├── SetLocaleFilter.php  # Locale auto-detection filter
├── LangView.php         # Console config template
└── README.md            # This file
```

The `Lang` class is registered as `'lang'` in the container and exposed via the `lang()` helper.

## Design Constraints

This is **intentionally** a simple system. Before reaching for more, know what it covers and what it doesn't.

### What it does well

- **Simple pluralization** — English-style `one|many` via pipe syntax
- **Basic locales** — en, es, fr, de, hi, and any language with singular/plural only
- **Zero dependencies** — no ICU, no gettext, no JSON catalogs
- **Small surface area** — learn it in 5 minutes

### What it does NOT do

- **Complex plural forms** — Russian, Arabic, Polish have 3-6 plural forms. This requires ICU MessageFormatter. If you need this, extend `choice()` with a library like `symfony/polyfill-intl-messageformatter`.
- **JSON or database-backed translations** — PHP arrays are fast, cacheable, and IDE-friendly. If you need real-time translation management via an admin panel, you'll need a heavier library.

This module is for teams that want to ship multilingual pages without learning a DSL or adding a dependency.
