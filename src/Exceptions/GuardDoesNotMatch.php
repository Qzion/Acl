<?php
/**
 *  _____  _____  _                         _
 * |     ||__   ||_| ___  ___     ___  ___ | |_
 * |  |  ||   __|| || . ||   | _ |   || -_||  _|
 * |__  _||_____||_||___||_|_||_||_|_||___||_|
 *    |__| hello@qzion.net
 */

namespace Qzion\Acl\Exceptions;

use Illuminate\Support\Collection;
use InvalidArgumentException;

class GuardDoesNotMatch extends InvalidArgumentException
{
    /**
     * This will throw an Invalid Argument Exception with a message that shows the laravel guard mismatch.
     *
     * @param string $givenGuard
     * @param Collection $expectedGuards
     *
     * @return static
     */
    public static function create(string $givenGuard, Collection $expectedGuards)
    {
        return new static("The given role or permission should use [{$expectedGuards->implode(', ')}] instead of [{$givenGuard}].");
    }

}