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

class PermissionDoesNotExist extends InvalidArgumentException
{
    /**
     * This will throw an Invalid Argument Exception with a message that there is no permission for the
     * given guard
     *
     * @param string $permissionName
     * @param string $guardName
     *
     * @return static
     */
    public static function create(string $permissionName, string $guardName =''){
        return new static("There is no permission named [{$permissionName}] for guard [{$guardName}]");
    }
}