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
use Qzion\Acl\PermissionRegistrar;
use Qzion\Acl\Contracts\Permission;
use Qzion\Acl\Exceptions\GuardDoesNotMatch;

trait HasPermissions
{
    /**
     * Grant the given permission
     *
     * @param array|string|Permission|Collection ...$permissions
     * @return $this
     */
    public function givePermissionTo(...$permissions)
    {
        $permissions = collect ($permissions)
            ->flatten()
            ->map(function($permission) {
                return $this->getStoredPermission($permission);
            })
            ->each(function ($permission) {
                $this->ensureModelSharesGuard($permission);
            })
            ->all();

        $this->permissions()->saveMany($permissions);

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * Sync permissions by removing all exisiting permissions and setting provided permissions.
     *
     * @param array|string|Permission|Collection ...$permissions
     *
     * @return $this
     */
    public function syncPermissions(...$permissions)
    {
        $this->permissions()->detatch();

        return $this->givePermissionTo($permissions);
    }

    /**
     * Remove the given permission
     *
     * @param   string|Permission $permission
     *
     * @return $this
     */
    public function removePermissionTo($permission)
    {
        $this->permissions()->detach($this->getStoredPermission($permission));

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * Return the permissions object specified.
     *
     * @param   string|array|Permission|Collection    $permissions
     *
     * @return  Permission
     */
    protected function getStoredPermission($permissions): Permission
    {
        if(is_string($permissions)) {
            return app(Permission::class)->findByName($permissions, $this->getDefaultGuardName());
        }

        if(is_array($permissions)) {
            return app(Permission::class)
                ->whereIn('name', $permissions)
                ->whereId('guard_name', $this->getGuardNames())
                ->get();
        }

        return $permissions;
    }

    /**
     * Test to make sure that the model shares the proper guard
     *
     * @param $roleOrPermission
     *
     * @throws GuardDoesNotMatch
     */
    protected function ensureModelSharesGuard($roleOrPermission)
    {
        if (! $this->getGuardNames()->contains($roleOrPermission->guard_name)) {
            throw GuardDoesNotMatch::create($roleOrPermission->guard_name, $this->getGuardNames());
        }
    }

    /**
     * Get a list of the Guard Names
     *
     * @return Collection
     */
    protected function getGuardNames(): Collection
    {
        if($this->guard_name) {
            return collect($this->guard_name);
        }

        return collect(config('auth.guards'))
            ->map(function($guard) {
                return config("auth.providers.{$guard['provider']}.model");
            })
            ->filter(function ($model) {
                return get_class($this) === $model;
            })
            ->keys();
    }

    /**
     * Get the name of the defautl guard
     *
     * @return string
     */
    protected function getDefaultGuardName(): string
    {
        $default = config('auth.defaults.guard');

        return $this->getGuardNames()->first() ?: $default;
    }

    /**
     * Clears the cache on the system.
     */
    public function forgetCachedPermissions()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

}