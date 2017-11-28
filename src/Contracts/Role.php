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
use Qzion\Acl\Exceptions\RoleDoesNotExist;

interface Role
{
    /**
     * A role may be given various permissions
     *
     * @return  BelongsToMany
     */
    public function permissions(): BelongsToMany;

    /**
     * Find a role by its name guard name.
     *
     * @param   string      $name
     * @param   string|null $guardName
     *
     * @throws RoleDoesNotExist
     *
     * @return Role
     */
    public static function findByName(string $name, $guardName): Role;

    /**
     * Determine if the user may perform the given permission.
     *
     * @param string|Permission $permission
     *
     * @return bool
     */
    public function hasPermissionTo($permission): bool;

}