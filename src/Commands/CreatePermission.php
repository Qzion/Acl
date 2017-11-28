<?php
/**
 *  _____  _____  _                         _
 * |     ||__   ||_| ___  ___     ___  ___ | |_
 * |  |  ||   __|| || . ||   | _ |   || -_||  _|
 * |__  _||_____||_||___||_|_||_||_|_||___||_|
 *    |__| hello@qzion.net
 */

namespace Qzion\Acl\Commands;

use Illuminate\Console\Command;
use Qzion\Acl\Contracts\Permission as PermissionContract;


class CreatePermission extends Command
{
    protected $signature = 'acl:create-permission
                            {name : The name of the permission} 
                            {guard? : The name of the guard}';

    protected $description = 'Creates a permission';

    public function handle()
    {
        $permissionClass = app(PermissionContract::class);

        $permission = $permissionClass::create([
            'name' => $this->argument('name'),
            'guard_name' => $this->argument('guard'),
        ]);

        $this->info("Permission [{$permission->name}] created");
    }
}