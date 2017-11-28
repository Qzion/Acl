<?php
/**
 *  _____  _____  _                         _
 * |     ||__   ||_| ___  ___     ___  ___ | |_
 * |  |  ||   __|| || . ||   | _ |   || -_||  _|
 * |__  _||_____||_||___||_|_||_||_|_||___||_|
 *    |__| hello@qzion.net
 */

namespace Qzion\Acl\Test;

use Illuminate\Support\Facades\Artisan;
use Qzion\Acl\Models\Role;
use Qzion\Acl\Models\Permission;

class CommandTest extends TestCase
{
    /** @test */
    public function check_if_command_can_create_a_role()
    {
        Artisan::call('acl:create-role', ['name' => 'new-role']);
        $this->assertCount(1, Role::where('name', 'new-role')->get());
    }
    /** @test */
    public function check_if_command_can_create_a_role_with_a_guard()
    {
        Artisan::call('acl:create-role', [
            'name' => 'new-role',
            'guard' => 'api',
        ]);
        $this->assertCount(1, Role::where('name', 'new-role')
            ->where('guard_name', 'api')
            ->get());
    }
    /** @test */
    public function check_if_command_can_create_a_permission()
    {
        Artisan::call('acl:create-permission', ['name' => 'new-permission']);
        $this->assertCount(1, Permission::where('name', 'new-permission')->get());
    }
    /** @test */
    public function check_if_command_can_create_a_permission_with_a_guard()
    {
        Artisan::call('acl:create-permission', [
            'name' => 'new-permission',
            'guard' => 'api',
        ]);
        $this->assertCount(1, Permission::where('name', 'new-permission')
            ->where('guard_name', 'api')
            ->get());
    }
}