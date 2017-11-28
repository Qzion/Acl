<?php
/**
 *  _____  _____  _                         _
 * |     ||__   ||_| ___  ___     ___  ___ | |_
 * |  |  ||   __|| || . ||   | _ |   || -_||  _|
 * |__  _||_____||_||___||_|_||_||_|_||___||_|
 *    |__| hello@qzion.net
 */

namespace Qzion\Acl\Test;

use Qzion\Acl\Exceptions\GuardDoesNotMatch;
use Qzion\Acl\Exceptions\PermissionDoesNotExist;

class HasPermissionTest extends TestCase
{
    /**
     * Test to see if you can assign a permission to a model
     *
     * @test
     */
    public function check_if_model_can_assign_permission()
    {
        $this->testUser->givePermissionTo($this->testUserPermission);

        $this->refreshTestUser();

        $this->assertTrue($this->testUser->hasPermissionTo($this->testUserPermission));
    }

    /**
     * Test to see if it throws an exception when assigning a permission that does not exist
     *
     * @throws PermissionDoesNotExist
     *
     * @test
     */
    public function check_if_exception_is_thrown_when_assigning_nonexistant_permission()
    {
        $this->expectException(PermissionDoesNotExist::class);

        $this->testUser->givePermissionTo('permission-does-not-exist');
    }

    /**
     * Test to see if it will throw an exception while trying to assign a permission from another guard
     *
     * @throws GuardDoesNotMatch
     * @throws PermissionDoesNotExist
     *
     * @test
     */
    public function check_if_exception_is_thrown_when_trying_to_assign_a_permission_from_another_guard()
    {
        $this->expectException(GuardDoesNotMatch::class);

        $this->testUser->givePermissionTo($this->testAdminPermission);

        $this->expectException(PermissionDoesNotExist::class);

        $this->testUser->givePermissionTo('admin-permission');
    }

    /**
     * Test to see if a permission can be removed from a model
     *
     * @test
     */
    public function check_if_permission_can_be_removed_from_model()
    {
        $this->testUser->givePermissionTo($this->testUserPermission);

        $this->refreshTestUser();

        $this->assertTrue($this->testUser->hasPermissionTo($this->testUserPermission));

        $this->testUser->removePermissionTo($this->testUserPermission);

        $this->refreshTestUser();

        $this->assertFalse($this->testUser->hasPermissionTo($this->testUserPermission));
    }

    /**
     * Test to see if you can scope model entries with permissions via a string
     *
     * @test
     */
    public function check_if_it_is_possible_to_scope_model_with_permission_string()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);
        $user1->givePermissionTo(['edit-articles', 'edit-news']);
        $this->testUserRole->givePermissionTo('edit-articles');
        $user2->assignRole('testRole');

        $scopedUsers1 = User::permission('edit-articles')->get();
        $scopedUsers2 = User::permission(['edit-news'])->get();

        $this->assertEquals($scopedUsers1->count(), 2);
        $this->assertEquals($scopedUsers2->count(), 1);
    }

    /**
     * Test to see if you can scope model entries with permissions via an array
     *
     * @test
     */
    public function check_if_it_is_possible_to_scope_model_with_permission_array()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);
        $user1->givePermissionTo(['edit-articles', 'edit-news']);
        $this->testUserRole->givePermissionTo('edit-articles');
        $user2->assignRole('testRole');

        $scopedUsers1 = User::permission(['edit-articles', 'edit-news'])->get();
        $scopedUsers2 = User::permission(['edit-news'])->get();

        $this->assertEquals($scopedUsers1->count(), 2);
        $this->assertEquals($scopedUsers2->count(), 1);
    }

    /**
     * Test to see if you can scope model entries with permissions via a collection
     *
     * @test
     */
    public function check_if_it_is_possible_to_scope_model_with_permission_collection()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);
        $user1->givePermissionTo(['edit-articles', 'edit-news']);
        $this->testUserRole->givePermissionTo('edit-articles');
        $user2->assignRole('testRole');

        $scopedUsers1 = User::permission(collect(['edit-articles', 'edit-news']))->get();
        $scopedUsers2 = User::permission(collect(['edit-news']))->get();

        $this->assertEquals($scopedUsers1->count(), 2);
        $this->assertEquals($scopedUsers2->count(), 1);
    }

    /**
     * Test to see if you can scope a model entries with permissions via an object
     *
     * @test
     */
    public function check_if_it_is_possible_to_scope_model_with_permission_object()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user1->givePermissionTo($this->testUserPermission->name);

        $scopedUsers1 = User::permission($this->testUserPermission)->get();
        $scopedUsers2 = User::permission([$this->testUserPermission])->get();

        $this->assertEquals($scopedUsers1->count(), 1);
        $this->assertEquals($scopedUsers2->count(), 1);
    }

    /**
     * Test to see if you can scope model entries with role permissions only
     *
     * @test
     */
    public function check_if_it_is_possible_to_scope_model_with_no_permissions_and_only_roles()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);
        $this->testUserRole->givePermissionTo('edit-articles');
        $user1->assignRole('testRole');
        $user2->assignRole('testRole');

        $scopedUsers = User::permission('edit-articles')->get();

        $this->assertEquals($scopedUsers->count(), 2);
    }

    /**
     * Test to see if you can scope model entries with permissions.
     *
     * @test
     */
    public function check_if_it_is_possible_to_scope_model_with_no_roles_and_only_permissions()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);
        $user1->givePermissionTo(['edit-news']);
        $user2->givePermissionTo(['edit-articles', 'edit-news']);

        $scopedUsers = User::permission('edit-news')->get();

        $this->assertEquals($scopedUsers->count(), 2);
    }

    /**
     * Test to see if an exception will be thrown when you try to scope a permission from another guard.
     *
     * @throws PermissionDoesNotExist
     * @throws GuardDoesNotMatch
     *
     * @test
     */
    public function check_if_exception_is_thrown_when_trying_to_scope_model_permission_from_another_guard()
    {
        $this->expectException(PermissionDoesNotExist::class);

        User::permission('testAdminPermission')->get();

        $this->expectException(GuardDoesNotMatch::class);

        User::permission($this->testAdminPermission)->get();
    }


}