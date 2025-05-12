# Lightpack RBAC (Role-Based Access Control)

A minimal, efficient, and modular Role-Based Access Control (RBAC) implementation for the Lightpack PHP framework.

---

## Features
- **RbacTrait for User Models:** Add roles and permissions to any model using a single trait.
- **ORM-Centric:** All relationships return query or collection objects for full chaining and efficiency.
- **Pivot Table Management:** Assign and remove roles/permissions using expressive, ORM-native methods.
- **Integrated Filtering:** Filter users by role or permission with scope methods (`scopeRole`, `scopePermission`).
- **Migration Included:** Instantly set up all necessary tables for roles, permissions, user-role, role-permission.
- **Highly Readable API:** Methods like `can`, `hasRole`, `assignRole`, etc., are clear and intuitive.
- **Fully Tested:** Comprehensive integration tests covering all features and edge cases.
- **Modular:** RBAC is opt-in. No pollution of the base user model.

---

## Database Schema

Tables created by the included migration:
- `roles`: Stores all roles.
- `permissions`: Stores all permissions.
- `user_role`: Pivot table linking users and roles.
- `role_permission`: Pivot table linking roles and permissions.

---

## Quick Start

1. **Run the Migration:**

2. **Add RbacTrait to Your User Model:**
   ```php
   use Lightpack\Rbac\RbacTrait;

   class User extends Model {
       use RbacTrait;
       // ...
   }
   ```

3. **Assign Roles to Users:**
   ```php
   $user->assignRole($roleId); // Attach a role by ID
   $user->removeRole($roleId); // Remove a role by ID
   ```

4. **Check Roles & Permissions:**
   ```php
   $user->hasRole('admin'); // true/false by role name or ID
   $user->can('edit_post'); // true/false by permission name or ID
   $user->cannot('delete_post'); // true/false
   $user->isSuperAdmin(); // true if user has 'superadmin' role
   ```

5. **Fetch All Roles or Permissions:**
   ```php
   $user->roles; // Collection of Role models
   $user->permissions; // Collection of Permission models (via roles)
   ```

6. **Filter Users by Role or Permission (Admin Panel, etc):**
   ```php
   // By role name
   $admins = User::filters(['role' => 'admin'])->all();
   // By role ID
   $editors = User::filters(['role' => 2])->all();
   // By permission name
   $canEdit = User::filters(['permission' => 'edit_post'])->all();
   // By permission ID
   $canDelete = User::filters(['permission' => 11])->all();
   // By both
   $filtered = User::filters(['role' => 'admin', 'permission' => 'edit_post'])->all();
   ```

---

## API Reference

### User (RbacTrait) Methods
- `roles()` — Returns a pivot relationship for user roles (chainable query).
- `hasRole($role)` — Checks if user has a role by name or ID.
- `assignRole($roleId)` — Assigns a role to the user.
- `removeRole($roleId)` — Removes a role from the user.
- `permissions()` — Returns a query object for all permissions via user roles.
- `can($permission)` — Checks if user has a permission by name or ID.
- `cannot($permission)` — Checks if user does NOT have a permission.
- `isSuperAdmin()` — Checks if user has the 'superadmin' role.
- `scopeRole($builder, $role)` — Filters users by role (name or ID) in queries.
- `scopePermission($builder, $permission)` — Filters users by permission (name or ID) in queries.

### Role Model Methods
- `permissions()` — Returns a query object for all permissions for a role.

---

## Example Usage

```php
// Assign and remove roles
$user->assignRole(2);
$user->removeRole(2);

// Check roles and permissions
if ($user->hasRole('admin')) {
    // ...
}
if ($user->can('publish_article')) {
    // ...
}

// Get all permissions for a user
$permissions = $user->permissions->all();

// Get all roles for a user
$roles = $user->roles->all();

// Filter users by role or permission
$admins = User::filters(['role' => 'admin'])->all();
$canEdit = User::filters(['permission' => 'edit_post'])->all();
```

---

## Migration Details

See `Migration.php` in this folder for the full schema. This migration:
- Creates all RBAC tables (`roles`, `permissions`, `user_role`, `role_permission`)
- Drops all tables.

---

## Integration Testing

- See `tests/Rbac/RbacTraitIntegrationTest.php` for comprehensive, real-world test coverage.
- All edge cases are covered: multiple roles, overlapping permissions, filtering, and more.
- Use these tests as a reference for correct usage and extension.

---

## Extending RBAC

- Add direct user-permission assignment by extending the trait.
- Build admin UIs, APIs, or CLIs for managing roles and permissions.
- Write additional tests as needed using Lightpack’s testing facilities.
---

## Troubleshooting

**Database connection errors:**
- Ensure your database config is correct and the DB server is running.
- The migration expects a `users` table to exist.

**Filters not working as expected:**
- Check that your `scopeRole` and `scopePermission` methods use table aliases to avoid SQL conflicts.
- Review the integration tests for working filter examples.

**Trait not working:**
- Confirm you are using `RbacTrait` (not any Laravel-style trait).
- Make sure your model extends Lightpack’s `Model` class.

---

## License
MIT
---

**Lightpack RBAC** — Secure, simple, and scalable access control for modern PHP apps.
