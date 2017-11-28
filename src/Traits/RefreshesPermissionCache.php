<?php
/**
 *  _____  _____  _                         _
 * |     ||__   ||_| ___  ___     ___  ___ | |_
 * |  |  ||   __|| || . ||   | _ |   || -_||  _|
 * |__  _||_____||_||___||_|_||_||_|_||___||_|
 *    |__| hello@qzion.net
 */

namespace Qzion\Acl\Traits;

use Qzion\Acl\PermissionRegistrar;


trait RefreshesPermissionCache
{
    public static function bootRefreshesPermissionCache()
    {
        // When the model saves something it will forget the cache
        static::saved(function () {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        // When the model deletes something it will forget the cache
        static::deleted(function () {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });

    }

}