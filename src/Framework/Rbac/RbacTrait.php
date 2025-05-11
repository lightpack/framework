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
     * Check if the user has a specific role (by name or id) using rbac_cache.
     */
    public function hasRole($role): bool
    {
        $cache = $this->getRbacCache();
        if (is_int($role)) {
            return array_key_exists($role, $cache['roles']);
        }
        if (is_string($role)) {
            return in_array($role, $cache['roles'], true);
        }
        return false;
    }

    /**
     * Super admin check using rbac_cache.
     */
    public function isSuperAdmin(): bool
    {
        $cache = $this->getRbacCache();
        return in_array('superadmin', $cache['roles'], true);
    }

    /**
     * Get RBAC cache (roles and permissions) from property or DB.
     */
    protected function getRbacCache(): array
    {
        if (!empty($this->rbac_cache)) {
            return $this->rbac_cache;
        }
        // Not cached: build from DB
        $roles = [];
        foreach ($this->roles as $r) {
            $roles[$r->id] = $r->name;
        }
        $permissions = [];
        foreach ($this->permissions as $p) {
            $permissions[$p->id] = $p->name;
        }
        $cache = ['roles' => $roles, 'permissions' => $permissions];
        $this->rbac_cache = $cache;
        $this->save(); // persist to DB
        return $cache;
    }

    /**
     * Assign a role to the user (by id).
     * @param int $roleId
     * @return self
     */
    public function assignRole(int $roleId): void
    {
        $this->roles()->attach($roleId);
        $this->rbac_cache = null;
        $this->save();
    }

    /**
     * Remove a role from the user (by id).
     * @param int $roleId
     * @return self
     */
    public function removeRole(int $roleId): void
    {
        $this->roles()->detach($roleId);
        $this->rbac_cache = null;
        $this->save();
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
     * Check if the user has a specific permission (by name or id) using rbac_cache.
     */
    public function can($permission): bool
    {
        $cache = $this->getRbacCache();
        if (is_int($permission)) {
            return array_key_exists($permission, $cache['permissions']);
        }
        if (is_string($permission)) {
            return in_array($permission, $cache['permissions'], true);
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
