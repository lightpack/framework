<?php

namespace Lightpack\Rbac;

use Lightpack\Rbac\Models\Role;
use Lightpack\Rbac\Models\Permission;

trait RbacTrait
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
     */
    public function hasRole($role): bool
    {
        $this->roles;

        if (is_int($role)) {
            return in_array($role, $this->roles->ids());
        }

        if (is_string($role)) {
            return in_array($role, $this->roles->column('name'), true);
        }

        return false;
    }

    /**
     * Check if the user is assigned superadmin role.
     */
    public function isSuperAdmin(): bool
    {
        return in_array('superadmin', $this->roles->column('name'), true);
    }

    /**
     * Assign one or more roles to the user.
     * @param int|array $roleIds Role ID or array of role IDs.
     * @return void
     */
    public function assignRole($roleIds): void
    {
        $this->roles()->attach($roleIds);
    }

    /**
     * Remove one or more roles from the user.
     * @param int|array $roleIds Role ID or array of role IDs.
     * @return void
     */
    public function removeRole($roleIds): void
    {
        $this->roles()->detach($roleIds);
    }

    /**
     * Get the permissions relationship for this user (via roles).
     */
    public function permissions()
    {
        return Permission::query()
            ->join('role_permission', 'permissions.id', 'role_permission.permission_id')
            ->whereIn('role_permission.role_id', $this->roles->ids())
            ->select('permissions.*')
            ->groupBy('permissions.id');
    }

    /**
     * Check if the user has a specific permission (by name or id).
     */
    public function can($permission): bool
    {
        $this->permissions;

        if (is_int($permission)) {
            return in_array($permission, $this->permissions->ids());
        }

        if (is_string($permission)) {
            return in_array($permission, $this->permissions->column('name'), true);
        }

        return false;
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

    /**
     * Scope: Filter users by role (name or id).
     * Usage: User::filters(['role' => 'admin'])
     */
    public function scopeRole($builder, $role)
    {
        $builder->join('user_role AS ur1', 'users.id', 'ur1.user_id')
                ->join('roles AS r1', 'ur1.role_id', 'r1.id');
        if (is_numeric($role)) {
            $builder->where('r1.id', $role);
        } else {
            $builder->where('r1.name', $role);
        }
    }

    /**
     * Scope: Filter users by permission (name or id).
     * Usage: User::filters(['permission' => 'edit_post'])
     */
    public function scopePermission($builder, $permission)
    {
        $builder->join('user_role AS ur2', 'users.id', 'ur2.user_id')
                ->join('roles AS r2', 'ur2.role_id', 'r2.id')
                ->join('role_permission', 'r2.id', 'role_permission.role_id')
                ->join('permissions', 'role_permission.permission_id', 'permissions.id');
        if (is_numeric($permission)) {
            $builder->where('permissions.id', $permission);
        } else {
            $builder->where('permissions.name', $permission);
        }
    }
}
