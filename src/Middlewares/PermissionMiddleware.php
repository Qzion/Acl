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


class PermissionMiddleware
{
    public function handle($request, Closure $next, $permission)
    {
        if (Auth::guest()) {
            throw Unauthorized::notLoggedIn();
        }

        $permissions = is_array($permission) ? $permission : explode('|', $permission);

        foreach ($permissions as $permission) {
            if(Auth::user()->can($permission)) {
                return $next($request);
            }
        }

        throw Unauthorized::forPermissions($permissions);
    }
}