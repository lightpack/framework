# Multi-Factor Authentication (MFA) for Lightpack Framework

## Overview
This module provides a robust, extensible Multi-Factor Authentication (MFA) system for the Lightpack PHP framework. It supports multiple authentication factors (methods), such as Email OTP, and is designed for easy extension to support SMS, TOTP, and more.

---

## Features
- **Pluggable MFA Factors:** Email (OTP) supported out of the box; SMS and TOTP are easy to add.
- **Job-Based Delivery:** Email OTPs are sent via job dispatching for flexibility and scalability.
- **Configurable OTP Generation:** Length, type (numeric, alpha, alnum, custom charset), and bypass codes are all configurable.
- **One-Time Use Codes:** OTPs are cached and invalidated after use.
- **Bypass/Fixed Code Support:** Easily set a bypass code for demo/testing via config.
- **Developer-Friendly:** Clean interfaces, dependency injection, and fluent utilities.

---

## Quick Start

### 1. Migration
Add the following fields to your `users` table:

```php
$table->varchar('mfa_method', 32)->nullable(); // User's chosen MFA factor
$table->boolean('mfa_enabled')->default(false); // Whether MFA is enabled
// For SMS (optional, if you plan to support SMS in future):
$table->varchar('mfa_phone', 20)->nullable();
$table->timestamp('mfa_phone_verified_at')->nullable();
```

---

### 2. Configuration (`config/mfa.php`)

```php
return [
    'default' => 'email',
    'enforce' => false,
    'factors' => [
        'email',
        // 'sms',
        // 'totp',
    ],
    'email' => [
        'code_length' => 6,
        'code_type' => 'numeric', // 'numeric', 'alpha', 'alnum', 'custom'
        'charset' => null,        // For custom types
        'bypass_code' => null,   // Set to a fixed code for demo/testing
        'ttl' => 300,            // Code validity in seconds
        'queue' => 'default',
        'max_attempts' => 1,
        'retry_after' => '60 seconds',
    ],
    // 'sms' => [...],
    // 'totp' => [...],
];
```

---

### 3. Usage Example

#### Sending an MFA Challenge
```php
$mfa = $container->get('mfa');
$mfa->send($user); // Sends an OTP via the configured factor (e.g., email)
```

#### Validating an MFA Code
```php
if ($mfa->validate($user, $inputCode)) {
    // MFA passed
}
```

---

## How It Works

### EmailMfa Factor
- Generates an OTP using the fluent `Otp` utility.
- Stores the OTP in cache with a unique key per user.
- Dispatches an `EmailMfaJob` to deliver the code.
- Validates user input and invalidates the OTP after one use.
- Honors a `bypass_code` in config for test/demo environments.

### Otp Utility
- Fluent, stateless OTP generator.
- Supports length, type, charset, and fixed/bypass code.
- Example:
  ```php
  $otp = (new Otp)
      ->length(6)
      ->type('alnum')
      ->charset('ABCDEFGHJKLMNPQRSTUVWXYZ23456789')
      ->generate();
  ```

### Extending with New Factors
- Implement the `MfaInterface` for your new factor (e.g., SMS, TOTP).
- Register your factor in the `MfaProvider`.
- Add any required fields to your migration and config.

---

## Using `MfaTrait` for Seamless MFA Integration

The `MfaTrait` provides convenience methods to add MFA functionality directly to your User model (or any model that represents an authenticatable entity).

### How to Use

1. **Add the Trait to Your Model:**

   ```php
   use Lightpack\Mfa\MfaTrait;

   class User
   {
       use MfaTrait;
       // ...
   }
   ```

2. **MFA Convenience Methods:**
   - `getMfaFactor()`: Returns the user's configured MFA factor instance.
   - `sendMfa()`: Sends an MFA challenge to the user using their preferred factor.
   - `validateMfa($input)`: Validates the user's MFA input using the configured factor.

### Example

```php
$user = // ... fetch user ...

// Send MFA challenge (e.g., email OTP)
$user->sendMfa();

// Validate MFA input
if ($user->validateMfa($inputCode)) {
    // MFA passed
}
```

### How It Works
- The trait uses the `mfa_method` property on the model to determine the user's MFA factor.
- Falls back to the default factor from config if not set.
- Integrates with the `mfa` service from the container.
- Keeps your controllers and services clean and focused.

### Benefits
- **Zero boilerplate:** Just add the trait.
- **User-centric:** MFA logic stays close to the user model.
- **Extensible:** Works with any factor registered in the MFA system.

---

## Testing
- Unit tests for the Otp utility are in `tests/utils/OtpTest.php`.
- (Recommended) Add tests for each MFA factor you implement.

---

## Security Notes
- **Never set `bypass_code` in production!**
- OTP codes are one-time use and expire after the configured TTL.
- Always verify user email/phone before enabling MFA for that factor.

---

## Roadmap
- [ ] Add SMS and TOTP factors
- [ ] Add backup/recovery codes
- [ ] Add UI helpers for MFA setup/verification

---

## References
- [Lightpack Documentation](https://lightpack.github.io/)
- [RFC 6238 - TOTP](https://datatracker.ietf.org/doc/html/rfc6238)

---

## Contributing
Pull requests and issues are welcome! Please ensure new factors are cleanly separated and well-tested.
