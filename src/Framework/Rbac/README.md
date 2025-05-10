# Lightpack RBAC (Role-Based Access Control)

A minimal, efficient, and modular Role-Based Access Control (RBAC) implementation for the Lightpack PHP framework.

---

## Features
- **Traits for Users:** Add roles and permissions to any model using traits.
- **ORM-Centric:** All relationships return query or collection objects for full chaining and efficiency.
- **Pivot Table Management:** Assign and remove roles/permissions using expressive, ORM-native methods.
- **Migration Included:** Instantly set up all necessary tables for roles, permissions, and pivots.
- **Highly Readable API:** Methods like `can`, `hasRole`, `assignRole`, etc., are clear and intuitive.
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
   ```bash
   php lightpack migrate src/Framework/Rbac/Migration.php
   ```

2. **Add Traits to Your User Model:**
   ```php
   use Lightpack\Framework\Rbac\Traits\RolesTrait;
   use Lightpack\Framework\Rbac\Traits\PermissionsTrait;

   class User extends Model {
       use RolesTrait, PermissionsTrait;
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
   ```

5. **Fetch All Roles or Permissions:**
   ```php
   $user->roles; // Collection of Role models
   $user->permissions; // Collection of Permission models (via roles)
   ```

---

## API Reference

### User Trait Methods
- `roles()` — Returns a pivot relationship for user roles (chainable query).
- `hasRole($role)` — Checks if user has a role by name or ID.
- `assignRole($roleId)` — Assigns a role to the user.
- `removeRole($roleId)` — Removes a role from the user.
- `permissions()` — Returns a query object for all permissions via user roles.
- `can($permission)` — Checks if user has a permission by name or ID.
- `cannot($permission)` — Checks if user does NOT have a permission.

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
```

---

## Migration Details

See `Migration.php` in this folder for the full schema. To roll back:
```bash
php lightpack migrate:rollback src/Framework/Rbac/Migration.php
```

---

## Extending RBAC
- Add direct user-permission assignment by extending the traits.
- Build an admin UI or CLI for managing roles and permissions.
- Write tests using Lightpack’s testing facilities.

---

## License
MIT

---

**Lightpack RBAC** — Secure, simple, and scalable access control for modern PHP apps.
