<?php

namespace Lightpack\Framework\Rbac\Traits;

use Lightpack\Framework\Rbac\Models\Permission;

/**
 * Trait to add permission management methods to a model (e.g., User).
 * Usage: use PermissionsTrait in your User model.
 */
trait PermissionsTrait
{
    /**
     * Get the permissions relationship for this user (via roles).
     */
    public function permissions()
    {
        return Permission::query()
            ->join('role_permission', 'permissions.id', '=', 'role_permission.permission_id')
            ->whereIn('role_permission.role_id', $this->roles->ids())
            ->select('permissions.*')
            ->groupBy('permissions.id');
    }

    /**
     * Check if the user has a specific permission (by name or id).
     * @param string|int $permission
     * @return bool
     */
    public function can($permission): bool
    {
        $key = is_string($permission) ? 'name' : 'id';

        return (bool) $this->permissions->first([
            $key => $permission
        ]);
    }

    /**
     * Check if the user does NOT have a specific permission.
     * @param string|int $permission
     * @return bool
     */
    public function cannot($permission): bool
    {
        return !$this->can($permission);
    }
}
