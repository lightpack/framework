# Lightpack Audit Facility

## Overview
The Lightpack Audit module provides a simple, explicit, and powerful way to track and record changes, actions, and events in your application. It is designed in the Lightpack philosophy: **no magic, no hidden observers, just clear and intentional code**.

Auditing is essential for security, compliance, debugging, and accountability. With Lightpack's audit facility, you can log who did what, when, and where—across your entire app.

---

## Features
- **Explicit API:** Log audit events anywhere in your code—no hidden hooks.
- **Configurable:** Choose what to audit and when to log.
- **Flexible Storage:** Audit logs are stored in a dedicated `audit_logs` table.
- **Rich Context:** Store user, action, before/after values, URL, IP, and more.
- **Easy Querying:** Use the `AuditLog` model to fetch and analyze audit data.

---

## Installation
1. **Run the Migration**
   - Ensure your database is configured.
   - Run the migration to create the `audit_logs` table:
     ```php
     // In your migration runner or CLI
     require 'src/Framework/Audit/Migration.php';
     ```

2. **Add the Audit Module**
   - The module consists of two main files:
     - `AuditLog.php` (the model)
     - `Audit.php` (the service)

---

## Usage

### Logging an Audit Event
Call the static `log` method from anywhere (controller, service, etc.):

```php
use Lightpack\Audit\Audit;

Audit::log([
    'user_id'        => $userId,              // (int|null) ID of the acting user
    'action'         => 'update',             // (string) Action performed
    'audit_type' => User::class,          // (string) table
    'audit_id'   => $user->id,            // (int|null) ID of affected record
    'old_values'     => $old,                 // (array|null) Before values
    'new_values'     => $new,                 // (array|null) After values
    'url'            => $request->url(),      // (string|null) Request URL
    'ip_address'     => $request->ip(),       // (string|null) IP address
    'user_agent'     => $request->userAgent(),// (string|null) User agent
    'message'        => 'User profile updated', // (string|null) Optional message
]);
```

All fields are optional except `action` and `audit_type` (for best practice).

---

## Logging System-Generated Events and Messages

You can log system-generated events (with no user) or add custom messages to any audit event using the `message` field. This is useful for tracking background jobs, cron tasks, or internal system actions.

### Example: System-Generated Event

```php
Audit::log([
    'user_id'    => null, // No user involved
    'action'     => 'system_cleanup',
    'audit_type' => 'Token',
    'audit_id'   => $tokenId,
    'old_values' => $tokenData,
    'new_values' => null,
    'message'    => 'Expired token removed by scheduled job',
    'user_agent' => 'system/cronjob',
]);
```

### Example: User Event with a Message

```php
Audit::log([
    'user_id'    => $userId,
    'action'     => 'login',
    'audit_type' => 'User',
    'audit_id'   => $userId,
    'message'    => 'User logged in successfully from web portal',
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent(),
]);
```

> The `message` field is a free-form text column for any extra context, system notes, or explanations you want to attach to an audit entry.

---

### Example: Auditing a Model Update

```php
$old = $user->toArray();
$user->update($data);
Audit::log([
    'user_id'        => $authUser->id,
    'action'         => 'update',
    'audit_type' => User::class,
    'audit_id'   => $user->id,
    'old_values'     => $old,
    'new_values'     => $user->toArray(),
    'url'            => $request->url(),
    'ip_address'     => $request->ip(),
    'user_agent'     => $request->userAgent(),
]);
```

---

## Best Practice: Logging Multi-Entity Actions

If a single action (such as deleting a role) causes changes in multiple tables or entities (e.g., roles and their attached permissions), **log a separate audit entry for each impacted entity**. This ensures your audit trail is complete and granular.

### Example: Cascading Delete (Role and Permissions)

Suppose you delete a role and it cascades to permissions:

```php
// Log the role deletion
Audit::log([
    'user_id'    => $adminId,
    'action'     => 'delete',
    'audit_type' => 'Role',
    'audit_id'   => $role->id,
    'old_values' => $role->toArray(),
    'message'    => 'Role deleted by admin',
]);

// Log each affected permission
foreach ($permissions as $permission) {
    Audit::log([
        'user_id'    => $adminId,
        'action'     => 'cascade_delete',
        'audit_type' => 'Permission',
        'audit_id'   => $permission->id,
        'old_values' => $permission->toArray(),
        'message'    => 'Permission unlinked/deleted due to role deletion',
    ]);
}
```

> Each audit entry's `audit_type` and `audit_id` point to the precise entity affected, making querying and reporting straightforward.

---

## AuditLog Helpers for Building Audit Views

The audit facility provides several helpers to make building audit log screens and APIs easier:

### 1. Resolving User Information: `user()`

The `AuditLog` model provides a `user()` relation that resolves the `user_id` field to an `AuthUser` instance.

```php
$log = AuditLog::query()->where('user_id', 5)->one();
echo $log->user->name; // Outputs the user's name
```

If the action was performed by the system (no user), `$log->user` will be `null`.

### 2. Computing Changes: `diff()`

You can compute the difference between `old_values` and `new_values` using the `diff()` method:

```php
$log = AuditLog::query()->where('id', 123)->one();
$diff = $log->diff();
// $diff['added'] contains fields added or changed
// $diff['removed'] contains fields removed or changed
```

### 3. Query Scopes & Filters

You can filter audit logs by user, action, or audit type using the `filters()` method:

```php
// By user
$logs = AuditLog::filters(['user' => 5])->all();
// By action
$logs = AuditLog::filters(['action' => 'update'])->all();
// By audit type
$logs = AuditLog::filters(['auditType' => 'User'])->all();
// Combine filters
$logs = AuditLog::filters([
    'user' => 5,
    'action' => 'delete',
    'auditType' => 'Post',
])->all();
```

These helpers make it much easier to build rich, user-friendly audit log screens and APIs.

---

## Schema

The `audit_logs` table has the following fields:

| Column         | Type      | Description                        |
| -------------- | --------- | ---------------------------------- |
| id             | bigint    | Primary key                        |
| user_id        | bigint    | Acting user ID (nullable)          |
| action         | varchar   | Action performed                   |
| audit_type | varchar   | Class/model/table                  |
| audit_id   | bigint    | Affected record ID (nullable)      |
| old_values     | text      | JSON-encoded old values (nullable) |
| new_values     | text      | JSON-encoded new values (nullable) |
| url            | varchar   | URL of the request (nullable)      |
| ip_address     | varchar   | IP address (nullable)              |
| user_agent     | varchar   | User agent (nullable)              |
| created_at     | datetime  | Timestamp                          |
| updated_at     | datetime  | Timestamp                          |

---

## Querying Audit Logs

Use the `AuditLog` model to fetch audit records:

```php
use Lightpack\Audit\AuditLog;

// Get all audits for a user
$auditLogs = AuditLog::query()where('user_id', $userId)->all();

// Get all audits for a specific table
$auditLogs = AuditLog::query()where('audit_type', User::class)
                    ->where('audit_id', $userId)
                    ->all();
```

---

## Philosophy
- **No Magic:** You control when and what gets audited.
- **Explicit:** All audit calls are visible in your code.
- **Composable:** Integrate with any part of your app.

---

## Future Enhancements
- Traits for auto-auditing model changes
- CLI tools for audit log analysis
- Pluggable storage (file, external service)
- UI for browsing and filtering audits

---
