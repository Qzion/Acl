<?php
/**
 *  _____  _____  _                         _
 * |     ||__   ||_| ___  ___     ___  ___ | |_
 * |  |  ||   __|| || . ||   | _ |   || -_||  _|
 * |__  _||_____||_||___||_|_||_||_|_||___||_|
 *    |__| hello@qzion.net
 */

namespace Qzion\Acl\Test;

use Qzion\Acl\Contracts\Role;
use Qzion\Acl\Exceptions\RoleDoesNotExist;
use Qzion\Acl\Exceptions\GuardDoesNotMatch;
use Qzion\Acl\Exceptions\PermissionDoesNotExist;

class HasRolesTest extends TestCase
{

    /**
     * Test to verify that a user does not have a role.
     *
     * @test
     */
    public function check_if_model_does_not_have_a_role ()
    {
        $this->assertFalse($this->testUser->hasRole('testRole'));
    }

    /**
     * Test to verify that a user can have a role assigned and removed
     *
     * @test
     */
    public function check_if_model_can_assign_and_remove_a_role ()
    {
        // Adds the role to test user and then checks to make sure user was assigned
        $this->testUser->assignRole('testRole');
        $this->assertTrue($this->testUser->hasRole('testRole'));

        // This removes the role from the test user and checks to make sure it was removed
        $this->testUser->removeRole('testRole');
        $this->refreshTestUser();
        $this->assertFalse($this->testUser->hasRole('testRole'));
    }

    /**
     * Test to verify a role can be added to to an object with a role object
     *
     * @test
     */
    public function check_if_model_can_add_a_role_with_object ()
    {
        $this->testUser->assignRole($this->testUserRole);
        $this->assertTrue($this->testUser->hasRole($this->testUserRole));
    }

    /**
     * Test to verify a list of roles can be added to an object
     *
     * @test
     */
    public function check_if_model_can_have_multiple_roles_assigned_by_string ()
    {
        $this->testUser->assignRole('testRole', 'testRole2');
        $this->assertTrue($this->testUser->hasAllRoles(collect(['testRole', 'testRole2'])));
    }

    /**
      * Test to verify a list of roles can be added to an object via an array
      *
      * @test
      */
    public function check_if_model_can_have_multiple_roles_assigned_by_array ()
    {
        $this->testUser->assignRole(['testRole', 'testRole2']);
        $this->assertTrue($this->testUser->hasAllRoles(collect(['testRole', 'testRole2'])));
    }

    /**
     * Test that if an object is given a role that doesnt exist it will throw exception
     *
     * @throws RoleDoesNotExist
     *
     * @test
     */
    public function check_if_exception_will_be_thrown_by_adding_a_role_that_does_not_exist ()
    {
        $this->expectException(RoleDoesNotExist::class);
        $this->testUser->assignRole('Non-Existant-Role');
    }
       
    /**
     * Test to verify you can only assign a role from the proper guard
     *
     * @throws RoleDoesNotExist
     *
     * @test
     */
    public function check_if_exception_will_be_thrwon_by_adding_a_role_from_a_different_guard ()
    {
        $this->expectException(RoleDoesNotExist::class);
        $this->testUser->assignRole('testAdminRole');
    }

    /**
     * Test that you can not assign a role from a different guard
     *
     * @throws GuardDoesNotMatch
     *
     * @test
     */
    public function it_throws_an_exception_when_assigning_a_role_from_a_different_guard()
    {
        $this->expectException(GuardDoesNotMatch::class);
        $this->testUser->assignRole($this->testAdminRole);
    }

    /**
     * Test that you can sync roles with a string
     *
     * @test
     */
    public function check_if_model_can_sync_all_roles_from_string()
    {
        $this->testUser->assignRole('testRole');
        $this->testUser->syncRoles('testRole2');
        $this->assertFalse($this->testUser->hasRole('testRole'));
        $this->assertTrue($this->testUser->hasRole('testRole2'));
    }

    /**
     * Test that you can sync multiple roles with strings
     *
     * @test
     */
    public function check_if_model_can_sync_multiple_roles_with_strings()
    {
        $this->testUser->syncRoles('testRole', 'testRole2');
        $this->assertTrue($this->testUser->hasAllRoles(collect(['testRole', 'testRole2'])));
    }

    /**
     * Test to verify a list of roles can be synced via an array
     *
     * @test
     */
    public function check_if_model_can_sync_multiple_roles_with_array()
    {
        $this->testUser->syncRoles(['testRole', 'testRole2']);
        $this->assertTrue($this->testUser->hasAllRoles(collect(['testRole', 'testRole2'])));
    }

    /**
     * Test to verify if you sync roles with blank array it will remove all roles
     *
     * @test
     */
    public function check_if_model_will_remove_all_roles_when_syncing_blank_array()
    {
        $this->testUser->assignRole('testRole');
        $this->testUser->assignRole('testRole2');
        $this->testUser->syncRoles([]);
        $this->assertFalse($this->testUser->hasAllRoles(collect(['testRole', 'testRole2'])));
    }

    /**
     * Throw exception when syncing a role from another guard
     *
     * @throws RoleDoesNotExist
     * @throws GuardDoesNotMatch
     *
     * @test
     */
    public function check_if_exception_will_be_thrown_when_trying_to_sync_role_from_another_guard()
    {
        $this->expectException(RoleDoesNotExist::class);
        $this->testUser->syncRoles('testRole', 'testAdminRole');
        $this->expectException(GuardDoesNotMatch::class);
        $this->testUser->syncRoles('testRole', $this->testAdminRole);
    }

    /**
     * Test to ensure that when you delete object it will delete related items on pivot tables
     *
     * @test
     */
    public function check_if_model_deletion_will_delete_pivot_table_entries()
    {
        $user = User::create(['email' => 'user@test.com']);
        $user->assignRole('testRole');
        $user->givePermissionTo('edit-articles');
        $this->assertDatabaseHas('model_has_permissions', ['model_id' => $user->id]);
        $this->assertDatabaseHas('model_has_roles', ['model_id' => $user->id]);
        $user->delete();
        $this->assertDatabaseMissing('model_has_permissions', ['model_id' => $user->id]);
        $this->assertDatabaseMissing('model_has_roles', ['model_id' => $user->id]);
    }

    /**
     * Test to see if you can scope role on the specified model and return all entries that are a part of that role
     *
     * @test
     */
    public function check_if_you_can_scope_model_with_role_strings()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);
        $user1->assignRole('testRole');
        $user2->assignRole('testRole2');
        $scopedUsers = User::role('testRole')->get();
        $this->assertEquals($scopedUsers->count(), 1);
    }

    /**
     * Test to see if the scope will allow you to pass multiple roles via an array
     *
     * @test
     */
    public function check_if_you_can_scope_model_with_role_array()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);
        $user1->assignRole($this->testUserRole);
        $user2->assignRole('testRole2');
        $scopedUsers1 = User::role([$this->testUserRole])->get();
        $scopedUsers2 = User::role(['testRole', 'testRole2'])->get();
        $this->assertEquals($scopedUsers1->count(), 1);
        $this->assertEquals($scopedUsers2->count(), 2);
    }

    /**
     * Test to see if the scope will allow you to pass multiple roles via a collection
     *
     * @test
     */
    public function check_if_you_can_scope_model_with_role_collection()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);
        $user1->assignRole($this->testUserRole);
        $user2->assignRole('testRole2');
        $scopedUsers1 = User::role([$this->testUserRole])->get();
        $scopedUsers2 = User::role(collect(['testRole', 'testRole2']))->get();
        $this->assertEquals($scopedUsers1->count(), 1);
        $this->assertEquals($scopedUsers2->count(), 2);
    }

    /**
     * Test to see if the scope will allow you to pass an object
     *
     * @test
     */
    public function check_if_you_can_scope_model_with_role_object()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);
        $user1->assignRole($this->testUserRole);
        $user2->assignRole('testRole2');
        $scopedUsers = User::role($this->testUserRole)->get();
        $this->assertEquals($scopedUsers->count(), 1);
    }

    /**
     * Test to see if the scope will throw an exception if using a role from another guard
     *
     * @throws RoleDoesNotExist
     * @throws GuardDoesNotMatch
     *
     * @test
     */
    public function checks_if_exception_is_thrown_when_scoping_role_from_different_guard()
    {
        $this->expectException(RoleDoesNotExist::class);
        User::role('testAdminRole')->get();
        $this->expectException(GuardDoesNotMatch::class);
        User::role($this->testAdminRole)->get();
    }

    /**
     * A test to see if any of the given roles belong to the model
     *
     * @test
     */
    public function checks_if_model_has_at_least_one_role()
    {
        $roleModel = app(Role::class);
        $roleModel->create(['name' => 'second role']);
        $this->assertFalse($this->testUser->hasRole($roleModel->all()));
        $this->testUser->assignRole($this->testUserRole);
        $this->refreshTestUser();
        $this->assertTrue($this->testUser->hasRole($roleModel->all()));
        $this->assertTrue($this->testUser->hasAnyRole($roleModel->all()));
        $this->assertTrue($this->testUser->hasAnyRole('testRole'));
        $this->assertFalse($this->testUser->hasAnyRole('role does not exist'));
        $this->assertTrue($this->testUser->hasAnyRole(['testRole']));
        $this->assertTrue($this->testUser->hasAnyRole(['testRole', 'role does not exist']));
        $this->assertFalse($this->testUser->hasAnyRole(['role does not exist']));
        $this->assertTrue($this->testUser->hasAnyRole('testRole', 'role does not exist'));
    }

    /**
     * A test to see if all the roles belong to the model
     *
     * @test
     */
    public function check_if_model_has_all_given_roles()
    {
        $roleModel = app(Role::class);
        $this->assertFalse($this->testUser->hasAllRoles($roleModel->first()));
        $this->assertFalse($this->testUser->hasAllRoles('testRole'));
        $this->assertFalse($this->testUser->hasAllRoles($roleModel->all()));
        $roleModel->create(['name' => 'second role']);
        $this->testUser->assignRole($this->testUserRole);
        $this->refreshTestUser();
        $this->assertFalse($this->testUser->hasAllRoles(['testRole', 'second role']));
        $this->testUser->assignRole('second role');
        $this->refreshTestUser();
        $this->assertTrue($this->testUser->hasAllRoles(['testRole', 'second role']));
    }

    /**
     * A test to see if a role from another guard belongs to a model
     *
     * @test
     * */
    public function checks_if_model_does_not_have_role_from_a_different_guard()
    {
        $this->assertFalse($this->testUser->hasRole('testAdminRole'));
        $this->assertFalse($this->testUser->hasRole($this->testAdminRole));
        $this->testUser->assignRole('testRole');
        $this->refreshTestUser();
        $this->assertTrue($this->testUser->hasAnyRole(['testRole', 'testAdminRole']));
        $this->assertFalse($this->testUser->hasAnyRole('testAdminRole', $this->testAdminRole));
    }

    /**
     * Test to determine that the user does not have a permission
     *
     * @test
     */
    public function checks_if_model_does_not_have_a_permission()
    {
        $this->assertFalse($this->testUser->hasPermissionTo('edit-articles'));
    }

    /**
     * Test to see if an exception is thrown when the permission associated to model does not exist
     *
     * @throws PermissionDoesNotExist
     *
     * @test
     * */
    public function checks_if_exception_is_thrown_if_trying_to_assign_a_permission_that_does_not_exist()
    {
        $this->expectException(PermissionDoesNotExist::class);
        $this->testUser->hasPermissionTo('does-not-exist');
    }

    /**
     * Test to see if an exception is thrown when permission does not exist for existing guard
     *
     * @throws PermissionDoesNotExist
     *
     * @test
     */
    public function checks_if_an_exception_is_thrown_if_the_permission_does_not_exist_in_existing_guard()
    {
        $this->expectException(PermissionDoesNotExist::class);
        $this->testUser->hasPermissionTo('admin-permission');
    }

    /**
     * A test that if the model does not have any permissions it will report properly
     *
     * @test
     */
    public function checks_if_model_will_work_without_any_permission()
    {
        $user = new User();
        $this->assertFalse($user->hasPermissionTo('edit-articles'));
    }

    /**
     * Test to see if a permission is attached directly to a model or to the role
     *
     * @test
     * */
    public function checks_if_model_has_permission_directly_as_a_string()
    {
        $this->assertFalse($this->testUser->hasAnyPermission('edit-articles'));
        $this->testUser->givePermissionTo('edit-articles');
        $this->refreshTestUser();
        $this->assertTrue($this->testUser->hasAnyPermission('edit-news', 'edit-articles'));
        $this->testUser->givePermissionTo('edit-news');
        $this->refreshTestUser();
        $this->testUser->removePermissionTo($this->testUserPermission);
        $this->assertTrue($this->testUser->hasAnyPermission('edit-articles', 'edit-news'));
    }

    /**
     * Test to see if a permission is attached directly to a model by passing permissions via an array
     *
     * @test
     */
    public function checks_if_model_has_permission_directly_as_a_array()
    {
        $this->assertFalse($this->testUser->hasAnyPermission(['edit-articles']));
        $this->testUser->givePermissionTo('edit-articles');
        $this->refreshTestUser();
        $this->assertTrue($this->testUser->hasAnyPermission(['edit-news', 'edit-articles']));
        $this->testUser->givePermissionTo('edit-news');
        $this->refreshTestUser();
        $this->testUser->removePermissionTo($this->testUserPermission);
        $this->assertTrue($this->testUser->hasAnyPermission(['edit-articles', 'edit-news']));
    }

    /**
     * Test to see if a permission is attached to the model by a role
     *
     * @test
     */
    public function checks_to_see_if_model_has_permissions_via_a_role()
    {
        $this->testUserRole->givePermissionTo('edit-articles');
        $this->testUser->assignRole('testRole');
        $this->assertTrue($this->testUser->hasAnyPermission('edit-news', 'edit-articles'));
    }

    /**
     * Test to see if the model has a direct permission
     *
     * @test
     */
    public function checks_if_model_has_direct_permissions()
    {
        $this->testUser->givePermissionTo('edit-articles');
        $this->refreshTestUser();
        $this->assertTrue($this->testUser->hasDirectPermission('edit-articles'));
        $this->testUser->removePermissionTo('edit-articles');
        $this->refreshTestUser();
        $this->assertFalse($this->testUser->hasDirectPermission('edit-articles'));
        $this->testUser->assignRole('testRole');
        $this->testUserRole->givePermissionTo('edit-articles');
        $this->refreshTestUser();
        $this->assertFalse($this->testUser->hasDirectPermission('edit-articles'));
    }

    /**
     * Test to make sure mmodel can list all the permissions through roles
     *
     * @test
     * */
    public function checks_if_model_can_list_all_permissions_via_role()
    {
        $roleModel = app(Role::class);
        $roleModel->findByName('testRole2')->givePermissionTo('edit-news');
        $this->testUserRole->givePermissionTo('edit-articles');
        $this->testUser->assignRole('testRole', 'testRole2');
        $this->assertEquals(
            collect(['edit-articles', 'edit-news']),
            $this->testUser->getPermissionsViaRoles()->pluck('name')
        );
    }

    /**
     * Test to see if permissions that match from direct attachment of model and belong to a role
     *
     * @test
     */
    public function checks_if_model_can_list_all_permisions_via_role_and_directly()
    {
        $this->testUser->givePermissionTo('edit-news');
        $this->testUserRole->givePermissionTo('edit-articles');
        $this->testUser->assignRole('testRole');
        $this->assertEquals(
            collect(['edit-articles', 'edit-news']),
            $this->testUser->getAllPermissions()->pluck('name')
        );
    }

    /**
     * Get all roles from the stated model
     *
     * @test
     */
    public function checks_if_model_can_list_all_roles()
    {
        $this->testUser->assignRole('testRole', 'testRole2');
        $this->assertEquals(
            collect(['testRole', 'testRole2']),
            $this->testUser->getRoleNames()
        );
    }

}