<?php
/**
 *  _____  _____  _                         _
 * |     ||__   ||_| ___  ___     ___  ___ | |_
 * |  |  ||   __|| || . ||   | _ |   || -_||  _|
 * |__  _||_____||_||___||_|_||_||_|_||___||_|
 *    |__| hello@qzion.net
 */

namespace Qzion\Acl;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Auth\Authenticatable;
use Qzion\Acl\Contracts\Permission;
use Qzion\Acl\Exceptions\PermissionDoesNotExist;


class PermissionRegistrar
{
    /**
     * @var Gate
     */
    protected $gate;

    /**
     * @var Repository
     */
    protected $cache;

    /**
     * @var string
     */
    protected $cacheKey = 'qzion.acl.cache';

    /**
     * PermissionRegistrar constructor.
     * @param Gate $gate
     * @param Repository $cache
     */
    public function __construct(Gate $gate, Repository $cache)
    {
        $this->gate = $gate;
        $this->cache = $cache;
    }

    /**
     * @return bool
     */
    public function registerPermissions(): bool
    {
        $this->gate->before(function (Authenticatable $user, string $ability) {
            try {
                if(method_exists($user, 'hasPermissionTo')) {
                    return $user->hasPermissionTo($ability);
                }
            } catch (PermissionDoesNotExist $e) {

            }
        });

        return true;
    }

    /**
     * Clears the permissions cache
     */
    public function forgetCachedPermissions()
    {
        $this->cache->forget($this->cacheKey);
    }

    /**
     * Get all of the permissions assigned to model
     *
     * @return Collection
     */
    public function getPermissions(): Collection
    {
        return $this->cache->remember($this->cacheKey, config('acl.cache_expiration_time'), function () {
            return app(Permission::class)->with('roles')->get();
        });
    }
}