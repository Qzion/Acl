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
use Qzion\Acl\Contracts\Role as RoleContract;

class CreateRole extends Command
{
    protected $signature = 'acl:create-role 
                                {name : The name of the permission} 
                                {guard? : The name of the guard}';

    protected $description = 'Create a role';

    public function handle()
    {
        $roleClass = app(RoleContract::class);

        $role = $roleClass::create([
            'name' => $this->argument('name'),
            'guard_name' => $this->argument('guard')
        ]);

        $this->info("Role [{$role->name}] created");
    }
}