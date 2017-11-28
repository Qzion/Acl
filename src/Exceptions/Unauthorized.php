<?php
/**
 *  _____  _____  _                         _
 * |     ||__   ||_| ___  ___     ___  ___ | |_
 * |  |  ||   __|| || . ||   | _ |   || -_||  _|
 * |__  _||_____||_||___||_|_||_||_|_||___||_|
 *    |__| hello@qzion.net
 */

namespace Qzion\Acl\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class Unauthorized extends HttpException
{
    /**
     * Exception if user does not have the right roles.
     *
     * @param array $roles
     * @return Unauthorized
     */
    public static function forRoles(array $roles): self
    {
        return new static(403, 'User does not have the right roles.', null, []);
    }

    /**
     * Exception if user does not have the right permissions
     *
     * @param array $permissions
     *
     * @return Unauthorized
     */
    public static function forPermissions(array $permissions): self
    {
        return new static(403, 'User does not have the right permissions.', null, []);
    }

    /**
     * Exception if the user is not logged in.
     *
     * @return Unauthorized
     */
    public static function notLoggedIn(): self
    {
        return new static(403, 'User is not logged in.', null, []);
    }

}