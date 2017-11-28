<?php
/**
 *  _____  _____  _                         _
 * |     ||__   ||_| ___  ___     ___  ___ | |_
 * |  |  ||   __|| || . ||   | _ |   || -_||  _|
 * |__  _||_____||_||___||_|_||_||_|_||___||_|
 *    |__| hello@qzion.net
 */

namespace Qzion\Acl\Contracts;


use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Qzion\Acl\Exceptions\PermissionDoesNotExist;

interface Permission
{
    /**
     * A Permission can be applied to roles.
     *
     * @return BelongsToMany
     */
    public function roles() : BelongsToMany;


    /**
     * Find a permission by its name.
     *
     * @param   string      $name
     * @param   string|null $guardName
     *
     * @throws PermissionDoesNotExist
     *
     * @return  Permission
     */
    public static function findByName(string $name, $guardName): Permission;
}