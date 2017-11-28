<?php
/**
 *  _____  _____  _                         _
 * |     ||__   ||_| ___  ___     ___  ___ | |_
 * |  |  ||   __|| || . ||   | _ |   || -_||  _|
 * |__  _||_____||_||___||_|_||_||_|_||___||_|
 *    |__| hello@qzion.net
 */

namespace Qzion\Acl\Middlewares;

use Closure;
use Illuminate\Support\Facades\Auth;
use Qzion\Acl\Exceptions\Unauthorized;

class RoleMiddleware
{
    public function handle($request, Closure $next, $role)
    {
        if (Auth::guest()) {
            throw Unauthorized::notLoggedIn();
        }

        $roles = is_array($role) ? $role : explode('|' , $role);

        if(! Auth::user()->hasAnyRole($roles)) {
            throw Unauthorized::forRoles($roles);
        }

        return $next ($request);
    }

}