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

use Illuminate\Support\Facades\DB;
use Qzion\Acl\Contracts\Role;
use Qzion\Acl\Contracts\Permission;
use Qzion\Acl\PermissionRegistrar;

class CacheTest extends TestCase
{
    const QUERIES_PER_CACHE_PROVISION = 2;

    /**
     * @var PermissionRegistrar
     */
    protected $registrar;

    /**
     * Setting Up the test
     */
    public function setUp()
    {
        parent::setUp();
        $this->registrar = app(PermissionRegistrar::class);
        $this->registrar->forgetCachedPermissions();
        DB::connection()->enableQueryLog();
    }

    /**
     * Function to count how many query results return
     *
     * @param int $expected
     */
    protected function assertQueryCount(int $expected)
    {
        $this->assertCount($expected, DB::getQueryLog());
    }

    /**
     * Function to flush the Query Log
     */
    protected function resetQueryCount()
    {
        DB::flushQueryLog();
    }

    /**
     * Checks to see if the registrar can cache a permission
     *
     * @test
     */
    public function check_if_registrar_can_cache_permission()
    {
        $this->registrar->getPermissions();
        $this->assertQueryCount(self::QUERIES_PER_CACHE_PROVISION);
        $this->registrar->getPermissions();
        $this->assertQueryCount(self::QUERIES_PER_CACHE_PROVISION);
    }

    /**
     * Checks if upon creating a new permission that the cache is flushed
     *
     * @test
     */
    public function check_if_upon_creating_permission_the_cache_is_reset()
    {
        app(Permission::class)->create(['name' => 'new']);
        $this->resetQueryCount();
        $this->registrar->getPermissions();
        $this->assertQueryCount(self::QUERIES_PER_CACHE_PROVISION);
    }

    /**
     * Checks if upon updating a permission that the cache is flushed
     *
     * @test
     */
    public function check_if_upon_update_permission_the_cache_is_reset()
    {
        $permission = app(Permission::class)->create(['name' => 'new']);
        $permission->name = 'other name';
        $permission->save();
        $this->resetQueryCount();
        $this->registrar->getPermissions();
        $this->assertQueryCount(self::QUERIES_PER_CACHE_PROVISION);
    }

    /**
     * Checks if upon creating a new role that the cache is flushed
     *
     * @test
     */
    public function check_if_upon_creating_role_the_cache_is_reset()
    {
        app(Role::class)->create(['name' => 'new']);
        $this->resetQueryCount();
        $this->registrar->getPermissions();
        $this->assertQueryCount(self::QUERIES_PER_CACHE_PROVISION);
    }

    /**
     * Checks if upon updating a role that the cache is flushed
     *
     * @test
     */
    public function check_if_upon_updating_role_the_cache_is_reset()
    {
        $role = app(Role::class)->create(['name' => 'new']);
        $role->name = 'other name';
        $role->save();
        $this->resetQueryCount();
        $this->registrar->getPermissions();
        $this->assertQueryCount(self::QUERIES_PER_CACHE_PROVISION);
    }

    /**
     * Checks if upon adding a permission to a role that the cache is flushed
     *
     * @test
     */
    public function check_if_upon_adding_permission_to_role_the_cache_is_reset()
    {
        $this->testUserRole->givePermissionTo($this->testUserPermission);
        $this->resetQueryCount();
        $this->registrar->getPermissions();
        $this->assertQueryCount(self::QUERIES_PER_CACHE_PROVISION);
    }

    /**
     * Verify if the haPermissionTo method is using the cache
     *
     * @test
     */
    public function checks_if_the_hasPermissionTo_method_uses_cache()
    {
        $this->testUserRole->givePermissionTo(['edit-articles', 'edit-news']);
        $this->testUser->assignRole('testRole');
        $this->resetQueryCount();
        $this->assertTrue($this->testUser->hasPermissionTo('edit-articles'));
        $this->assertQueryCount(self::QUERIES_PER_CACHE_PROVISION + 2); // + 2 for getting the User's relations
        $this->resetQueryCount();
        $this->assertTrue($this->testUser->hasPermissionTo('edit-news'));
        $this->assertQueryCount(0);
        $this->assertTrue($this->testUser->hasPermissionTo('edit-articles'));
        $this->assertQueryCount(0);
    }



}