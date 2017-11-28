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

class RoleDoesNotExist extends InvalidArgumentException
{
    /**
     * This will throw an Invalid Argument Exception with a message that states there is no
     * role that was stated.
     *
     * @param string $roleName
     *
     * @return static
     */
    public static function create(string $roleName)
    {
        return new static("There is no role named [{$roleName}]");
    }
}