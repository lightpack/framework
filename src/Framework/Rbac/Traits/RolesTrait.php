<?php

namespace Lightpack\Framework\Rbac\Traits;

use Lightpack\Framework\Rbac\Models\Role;

/**
 * Trait to add role management methods to a model (e.g., User).
 * Usage: use RolesTrait in your User model.
 */
trait RolesTrait
{
    /**
     * Get the roles relationship for this user.
     *
     * @return \Lightpack\Database\Lucid\Pivot
     */
    public function roles()
    {
        return $this->pivot(Role::class, 'user_role', 'user_id', 'role_id');
    }

    /**
     * Check if the user has a specific role (by name or id).
     * @param string|int $role
     * @return bool
     */
    public function hasRole($role): bool
    {
        $key = is_string($role) ? 'name' : 'id';
        return (bool) $this->roles->first([
            $key => $role
        ]);
    }

    /**
     * Assign a role to the user (by id).
     * @param int $roleId
     * @return self
     */
    public function assignRole(int $roleId): void
    {
        $this->roles()->attach($roleId);
    }

    /**
     * Remove a role from the user (by id).
     * @param int $roleId
     * @return self
     */
    public function removeRole(int $roleId): void
    {
        $this->roles()->detach($roleId);
    }
}
