<?php
/**
 *  _____  _____  _                         _
 * |     ||__   ||_| ___  ___     ___  ___ | |_
 * |  |  ||   __|| || . ||   | _ |   || -_||  _|
 * |__  _||_____||_||___||_|_||_||_|_||___||_|
 *    |__| hello@qzion.net
 * Namespace: Qzion\Acl\Test
 */

namespace Qzion\Acl\Test;

use Qzion\Acl\Contracts\Permission;
use Qzion\Acl\Exceptions\PermissionAlreadyExists;


class PermissionTest extends TestCase
{
    /**
     * Checks to see if exception is thrown if multiple permissions are created
     *
     * @throws PermissionAlreadyExists
     *
     * @test
     */
    public function checks_if_exception_is_thrown_if_permission_exist()
    {
        $this->expectException(PermissionAlreadyExists::class);

        app(Permission::class)->create(['name' => 'test-permission']);
        app(Permission::class)->create(['name' => 'test-permission']);
    }

    /**
     * Checks to see if a permission can belong to a specified guard
     *
     * @test
     */
    public function checks_if_permission_can_belong_to_a_guard()
    {
        $permission = app(Permission::class)->create(['name' => 'can-edit', 'guard_name' => 'admin']);
        $this->assertEquals('admin', $permission->guard_name);
    }

    /**
     * Checks the default guard is the guard being used
     *
     * @test
     */
    public function checks_permissions_default_guard()
    {
        $this->assertEquals($this->app['config']->get('auth.defaults.guard'), $this->testUserPermission->guard_name);
    }

    /**
     * Checks that permissions can be assigned and registered to a model
     *
     * @test
     */
    public function checks_that_permissions_can_be_assigned_and_register_to_model()
    {
        $this->testAdmin->givePermissionTo($this->testAdminPermission);
        $this->testUser->givePermissionTo($this->testUserPermission);

        $this->assertCount(1, $this->testUserPermission->users);
        $this->assertTrue($this->testUserPermission->users->first()->is($this->testUser));
        $this->assertInstanceOf(User::class, $this->testUserPermission->users->first());
    }
}