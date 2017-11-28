<?php
/**
 *  _____  _____  _                         _
 * |     ||__   ||_| ___  ___     ___  ___ | |_
 * |  |  ||   __|| || . ||   | _ |   || -_||  _|
 * |__  _||_____||_||___||_|_||_||_|_||___||_|
 *    |__| hello@qzion.net
 */

namespace Qzion\Acl\Exceptions;

use InvalidArgumentException;


class RoleAlreadyExists extends InvalidArgumentException
{
    /**
     * This will throw an Invalid Argument Exception with a message that there is already a role that
     * exists for the given guard.
     *
     * @param string $roleName
     * @param string $guardName
     *
     * @return static
     */
    public static function create(string $roleName, string $guardName)
    {
        return new static ("A role [{$roleName}] already exists for guard [{$guardName}].");
    }
}