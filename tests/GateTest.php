<?php
/**
 *  _____  _____  _                         _
 * |     ||__   ||_| ___  ___     ___  ___ | |_
 * |  |  ||   __|| || . ||   | _ |   || -_||  _|
 * |__  _||_____||_||___||_|_||_||_|_||___||_|
 *    |__| hello@qzion.net
 */

namespace Qzion\Acl\Test;

use Illuminate\Contracts\Auth\Access\Gate;

class GateTest extends TestCase
{
    /**
     * Test to determine if a model entry has a permission
     *
     * @test
     */
    public function determine_if_model_has_permission()
    {
        $this->assertFalse($this->testUser->can('edit-articles'));
    }

    /**
     * Test to determine if permission check goes through multiple gates
     *
     * @test
     */
    public function checks_multiple_gates_for_permission()
    {
        $this->assertFalse($this->testUser->can('edit-articles'));

        app(Gate::class)->before(function () {
            return true;
        });

        $this->assertFalse($this->testUser->can('edit-articles'));
    }

    /**
     * Determine if a model has a permission
     *
     * @test
     */
    public function determine_if_model_has_direct_permission()
    {
        $this->testUser->givePermissionTo('edit-articles');

        $this->assertTrue($this->testUser->can('edit-articles'));

        $this->assertFalse($this->testUser->can('non-existing-permission'));

        $this->assertFalse($this->testUser->can('admin-permission'));
    }

    /**
     * Test to see if model has a permission through roles
     *
     * @test
     */
    public function check_to_see_if_it_has_permissions_through_roles()
    {
        $this->testUserRole->givePermissionTo($this->testUserPermission);

        $this->testUser->assignRole($this->testUserRole);

        $this->assertTrue($this->testUser->hasPermissionTo($this->testUserPermission));

        $this->assertTrue($this->testUser->can('edit-articles'));

        $this->assertFalse($this->testUser->can('non-existing-permission'));

        $this->assertFalse($this->testUser->can('admin-permission'));
    }

    /**
     * Test to see if model with a different guard has permission through roles
     *
     * @test
     */
    public function check_to_see_if_user_on_different_guard_has_permission_through_roles()
    {
        $this->testAdminRole->givePermissionTo($this->testAdminPermission);

        $this->testAdmin->assignRole($this->testAdminRole);

        $this->assertTrue($this->testAdmin->hasPermissionTo($this->testAdminPermission));

        $this->assertTrue($this->testAdmin->can('admin-permission'));

        $this->assertFalse($this->testAdmin->can('non-existing-permission'));

        $this->assertFalse($this->testAdmin->can('edit-articles'));
    }

}