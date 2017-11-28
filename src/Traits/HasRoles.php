<?php
/**
 *  _____  _____  _                         _
 * |     ||__   ||_| ___  ___     ___  ___ | |_
 * |  |  ||   __|| || . ||   | _ |   || -_||  _|
 * |__  _||_____||_||___||_|_||_||_|_||___||_|
 *    |__| hello@qzion.net
 */

namespace Qzion\Acl\Traits;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Qzion\Acl\Contracts\Role;
use Qzion\Acl\Contracts\Permission;


trait HasRoles
{
    use HasPermissions;

    /**
     * If you are deleting a model id it will detach the permissions and roles to said model in DB
     */
    public static function bootHasRoles()
    {
        static::deleting(function($model) {
            if (method_exists($model, 'is_ForceDeleting') && $model->isForceDeleting()) {
                return;
            }

            $model->roles()->detach();
            $model->permissions()->detach();
        });
    }

    /**
     * Relationship between your model and the roles table
     *
     * @return MorphToMany
     */
    public function roles(): MorphToMany
    {
        return $this->morphToMany(
            config('acl.models.role'),
            'model',
            config('acl.table_names.model_has_roles'),
            'model_id',
            'role_id'
        );
    }

    /**
     * Relationship between your model and the permissions table
     * @return MorphToMany
     */
    public function permissions(): MorphToMany
    {
        return $this->morphToMany(
            config('acl.models.permission'),
            'model',
            config('acl.table_names.model_has_permissions'),
            'model_id',
            'permission_id'
        );


    }

    /**
     * Normalize the permissions
     *
     * @param string|array|Permission|Collection    $permissions
     *
     * @return array
     */
    public function convertToPermissionModels($permissions): array
    {
        if ($permissions instanceof Collection) {
            $permissions = $permissions->toArray();
        }

        $permissions = array_wrap($permissions);

        return array_map(function ($permission) {
            if ($permission instanceof Permission) {
                return $permission;
            }

            return app(Permission::class)->findByName($permission, $this->getDefaultGuardName());
        }, $permissions);

    }

    /**
     * Scope to search for certain roles
     *
     * @param Builder                   $query
     * @param string|array|Collection   $roles
     *
     * @return Builder
     */
    public function scopeRole(Builder $query, $roles): Builder
    {
        if ($roles instanceof Collection) {
            $roles = $roles->toArray();
        }

        if(! is_array($roles)) {
            $roles = [$roles];
        }

        $roles = array_map(function($role) {
            if ($role instanceof Role) {
                return $role;
            }

            return app(Role::class)->findByName($role, $this->getDefaultGuardName());
        }, $roles);

        return $query->whereHas('roles', function ($query) use ($roles) {
            $query->where(function ($query) use ($roles) {
                foreach ($roles as $role) {
                    $query->orWhere(config('acl.table_names.roles').'.id', $role->id);
                }
            });
        });
    }

    /**
     * Scope to search for permissions
     *
     * @param Builder                               $query
     * @param string|array|Permission|Collection    $permissions
     *
     * @return Builder
     */
    public function scopePermission(Builder $query, $permissions): Builder
    {
        $permissions = $this->convertToPermissionModels($permissions);

        $rolesWithPermission = array_unique(array_reduce($permissions, function ($result, $permission) {
            return array_merge($result, $permission->roles->all());
        }, []));

        return $query->where(function ($query) use ($permissions, $rolesWithPermission) {
            $query->whereHas('permissions', function ($query) use ($permissions) {
                $query->where(function ($query) use ($permissions) {
                    foreach ($permissions as $permission) {
                        $query->orWhere(config('acl.table_names.permissions').'.id', $permission->id);
                    }
                });
            });
            if(count($rolesWithPermission) > 0) {
                $query->orWhereHas('roles', function ($query) use ($rolesWithPermission) {
                    $query->where(function ($query) use ($rolesWithPermission) {
                        foreach ($rolesWithPermission as $role) {
                            $query->orWhere(config('acl.table_names.roles').'.id', $role->id);
                        }
                    });
                });
            }
        });
    }

    /**
     * Assign a role or roles to a specified model
     *
     * @param   array|string|Role     ...$roles
     *
     * @return  $this
     */
    public function assignRole(...$roles)
    {
        $roles = collect($roles)
            ->flatten()
            ->map(function ($role) {
                return $this->getStoredRole($role);
            })
            ->each(function ($role) {
                $this->ensureModelSharesGuard($role);
            })
            ->all();

        $this->roles()->saveMany($roles);

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * Remove a given role from the model
     *
     * @param   string|Role   $role
     */
    public function removeRole($role)
    {
        $this->roles()->detach($this->getStoredRole($role));
    }

    /**
     * Sync the roles to a model by removing all roles and setting chosen ones.
     *
     * @param   array|string|Role  ...$roles
     * @return  $this
     */
    public function syncRoles(...$roles)
    {
        $this->roles()->detach();
        return $this->assignRole($roles);
    }

    /**
     * Checks to see if there are any roles attached to the model in question
     *
     * @param   string|array|Role|Collection    $roles
     *
     * @return  bool
     */
    public function hasRole($roles): bool
    {
        if(is_string($roles) && false !== strpos($roles,'|')) {
            $roles = $this->convertPipeToArray($roles);
        }

        if(is_string($roles)) {
            return $this->roles->contains('name', $roles);
        }

        if($roles instanceof Role){
            return $this->roles->contains('id', $roles->id);
        }

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role)) {
                    return true;
                }
            }

            return false;
        }

        return $roles->intersect($this->roles)->isNotEmpty();
    }

    /**
     * Determine if the model has any of the given role(s)
     *
     * @param   string|array|Role|Collection  $roles
     *
     * @return  bool
     *
     * @deprecated Use hasRole($roles) instead.
     */
    public function hasAnyRole($roles): bool
    {
        return $this->hasRole($roles);
    }

    /**
     * Determine if the model has all the given roles
     *
     * @param   string|Role|Collection    $roles
     * @return  bool
     */
    public function hasAllRoles($roles): bool
    {
        if (is_string($roles) && false !== strpos($roles, '|')){
            $roles = $this->convertPipeToArray($roles);
        }

        if (is_string($roles)){
            return $this->roles->contains('name', $roles);
        }

        if($roles instanceof Role){
            return $this->roles->contains('id', $roles->id);
        }

        $roles = collect()->make($roles)->map(function ($role) {
            return $role instanceof Role ? $role->name : $role;
        });

        return $roles->intersect($this->roles->pluck('name')) == $roles;
    }

    /**
     * Determine if the model may perform the given permission
     *
     * @param   string|Permission     $permission
     * @param   string|null           $guardName
     *
     * @return bool
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if (is_string($permission)){
            $permission = app(Permission::class)
                ->findByName($permission, $guardName ?? $this->getDefaultGuardName());
        }

        return $this->hasDirectPermission($permission) || $this->hasPermissionViaRole($permission);
    }

    /**
     * Determine if the model has any of the given permissions.
     *
     * @param array ...$permissions
     * @return bool
     */
    public function hasAnyPermission(...$permissions): bool
    {
        if (is_array($permissions[0])) {
            $permissions = $permissions[0];
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermissionTo($permission)){
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if model has, via roles the given permission
     *
     * @param Permission $permission
     *
     * @return bool
     */
    protected function hasPermissionViaRole(Permission $permission): bool
    {
        return $this->hasRole($permission->roles);
    }

    /**
     * Determine if the model has the given permission directly
     *
     * @param   string|Permission   $permission
     *
     * @return  bool
     */
    public function hasDirectPermission($permission): bool
    {
        if(is_string($permission)) {
            $permission = app(Permission::class)->findByName($permission, $this->getDefaultGuardName());

            if (! $permission) {
                return false;
            }
        }

        return $this->permissions->contains('id', $permission->id);
    }

    /**
     * Return all the permissions model has via roles.
     *
     * @return Collection
     */
    public function getPermissionsViaRoles(): Collection
    {
        return $this->load('roles', 'roles.permissions')->roles->flatMap(function ($role) {
            return $role->permissions;
        })->sort()->values();
    }

    /**
     * Return all permissions a model has via roles or direct.
     *
     * @return Collection
     */
    public function getAllPermissions(): Collection
    {
        return $this->permissions
            ->merge($this->getPermissionsViaRoles())
            ->sort()
            ->values();
    }

    /**
     * Returns all roles for given model
     *
     * @return Collection
     */
    public function getRoleNames(): Collection
    {
        return $this->roles->pluck('name');
    }

    /**
     * Return the role object
     *
     * @param   string|Role   $role
     *
     * @return  Role
     */
    protected function getStoredRole($role): Role
    {
        if(is_string($role)){
            return app(Role::class)->findByName($role, $this->getDefaultGuardName());
        }

        return $role;
    }

    /**
     * Will convert a string with the pipe symbol | into an array
     *
     * @param   string  $pipeString
     *
     * @return  array
     */
    protected function convertPipeToArray(string $pipeString): array
    {
        $pipeString = trim($pipeString);

        if(strlen($pipeString) <= 2) {
            return $pipeString;
        }

        $quoteCharacter = substr($pipeString, 0,1);
        $endCharacter = substr($quoteCharacter, -1, 1);

        if($quoteCharacter !== $endCharacter) {
            return explode('|', $pipeString);
        }

        if(! in_array($quoteCharacter, ["'",'"'])) {
            return explode('|', $pipeString);
        }

        return explode('|', trim($pipeString, $quoteCharacter));
    }

}