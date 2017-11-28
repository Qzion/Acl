<?php
/**
 *  _____  _____  _                         _
 * |     ||__   ||_| ___  ___     ___  ___ | |_
 * |  |  ||   __|| || . ||   | _ |   || -_||  _|
 * |__  _||_____||_||___||_|_||_||_|_||___||_|
 *    |__| hello@qzion.net
 */

namespace Qzion\Acl\Models;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Qzion\Acl\PermissionRegistrar;
use Qzion\Acl\Traits\RefreshesPermissionCache;
use Qzion\Acl\Exceptions\PermissionDoesNotExist;
use Qzion\Acl\Exceptions\PermissionAlreadyExists;
use Qzion\Acl\Contracts\Permission as PermissionContract;



class Permission extends Model implements PermissionContract
{
    use RefreshesPermissionCache;

    public $guarded = ['id'];

    /**
     * Permission constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? config('auth.defaults.guard');

        parent::__construct($attributes);

        $this->setTable(config('acl.table_names.permissions'));
    }

    /**
     * Create a Permission
     *
     * @param array $attributes
     *
     * @throws PermissionAlreadyExists
     *
     * @return $this|Model
     */
    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? config('auth.defaults.guard');

        if(static::getPermissions()->where('name', $attributes['name'])->where('guard_name', $attributes['guard_name'])->first()) {
            throw PermissionAlreadyExists::create($attributes['name'], $attributes['guard_name']);
        }

        if(app()::VERSION < '5.4') {
            return parent::create($attributes);
        }

        return static::query()->create($attributes);
    }

    /**
     * A Permission can be applied to roles.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('acl.models.role'),
            config('acl.table_names.role_has_permissions')
        );
    }

    /**
     * A Permission belongs to some users of the model associated with its guard.
     *
     * @return MorphToMany
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            getModelForGuard($this->attributes['guard_name']),
            'model',
            config('acl.table_names.model_has_permissions'),
            'permission_id',
            'model_id'
        );
    }

    /**
     * Find a permission by its name.
     *
     * @param   string $name
     * @param   string|null $guardName
     *
     * @throws PermissionDoesNotExist
     *
     * @return  PermissionContract
     */
    public static function findByName(string $name, $guardName = null): PermissionContract
    {
        $guardName = $guardName ?? config ('auth.defaults.guard');

        $permission = static::getPermissions()->where('name', $name)->where('guard_name', $guardName)->first();

        if (! $permission) {
            throw PermissionDoesNotExist::create($name, $guardName);
        }

        return $permission;
    }

    /**
     * Get the current cached permissions.
     *
     * @return Collection
     */
    protected static function getPermissions(): Collection
    {
        return app(PermissionRegistrar::class)->getPermissions();
    }
}