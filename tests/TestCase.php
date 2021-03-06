<?php
/**
 *  _____  _____  _                         _
 * |     ||__   ||_| ___  ___     ___  ___ | |_
 * |  |  ||   __|| || . ||   | _ |   || -_||  _|
 * |__  _||_____||_||___||_|_||_||_|_||___||_|
 *    |__| hello@qzion.net
 */

namespace Qzion\Acl\Test;

use Monolog\Handler\TestHandler;
use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as Orchestra;
use Qzion\Acl\AclServiceProvider;
use Qzion\Acl\PermissionRegistrar;
use Qzion\Acl\Contracts\Permission;
use Qzion\Acl\Contracts\Role;

abstract class TestCase extends Orchestra
{
    /** @var \Qzion\Acl\Test\User */
    protected $testUser;
    /** @var \Qzion\Acl\\Test\Admin */
    protected $testAdmin;
    /** @var \Qzion\Acl\Models\Role */
    protected $testUserRole;
    /** @var \Qzion\Acl\Models\Role */
    protected $testAdminRole;
    /** @var \Qzion\Acl\Models\Permission */
    protected $testUserPermission;
    /** @var \Qzion\Acl\Models\Permission */
    protected $testAdminPermission;
    public function setUp()
    {
        parent::setUp();
        $this->setUpDatabase($this->app);
        $this->reloadPermissions();
        $this->testUser = User::first();
        $this->testUserRole = app(Role::class)->find(1);
        $this->testUserPermission = app(Permission::class)->find(1);
        $this->testAdmin = Admin::first();
        $this->testAdminRole = app(Role::class)->find(3);
        $this->testAdminPermission = app(Permission::class)->find(3);
        $this->clearLogTestHandler();
    }
    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            AclServiceProvider::class,
        ];
    }
    /**
     * Set up the environment.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('view.paths', [__DIR__.'/resources/views']);
        // Set-up admin guard
        $app['config']->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'admins']);
        $app['config']->set('auth.providers.admins', ['driver' => 'eloquent', 'model' => Admin::class]);
        // Use test User model for users provider
        $app['config']->set('auth.providers.users.model', User::class);
        $app['log']->getMonolog()->pushHandler(new TestHandler());
    }
    /**
     * Set up the database.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpDatabase($app)
    {
        $app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->softDeletes();
        });
        $app['db']->connection()->getSchemaBuilder()->create('admins', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
        });
        include_once __DIR__.'/../src/database/migrations/create_acl_tables.php.stub';
        (new \CreateAclTables())->up();
        User::create(['email' => 'test@user.com']);
        Admin::create(['email' => 'admin@user.com']);
        $app[Role::class]->create(['name' => 'testRole']);
        $app[Role::class]->create(['name' => 'testRole2']);
        $app[Role::class]->create(['name' => 'testAdminRole', 'guard_name' => 'admin']);
        $app[Permission::class]->create(['name' => 'edit-articles']);
        $app[Permission::class]->create(['name' => 'edit-news']);
        $app[Permission::class]->create(['name' => 'admin-permission', 'guard_name' => 'admin']);
    }
    /**
     * Reload the permissions.
     */
    protected function reloadPermissions()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
    /**
     * Refresh the testuser.
     */
    public function refreshTestUser()
    {
        $this->testUser = $this->testUser->fresh();
    }
    /**
     * Refresh the testAdmin.
     */
    public function refreshTestAdmin()
    {
        $this->testAdmin = $this->testAdmin->fresh();
    }
    protected function clearLogTestHandler()
    {
        collect($this->app['log']->getMonolog()->getHandlers())->filter(function ($handler) {
            return $handler instanceof TestHandler;
        })->first(function (TestHandler $handler) {
            $handler->clear();
        });
    }
    protected function assertNotLogged($message, $level)
    {
        $this->assertFalse($this->hasLog($message, $level), "Found `{$message}` in the logs.");
    }
    protected function assertLogged($message, $level)
    {
        $this->assertTrue($this->hasLog($message, $level), "Couldn't find `{$message}` in the logs.");
    }
    /**
     * @param $message
     * @param $level
     *
     * @return bool
     */
    protected function hasLog($message, $level)
    {
        return collect($this->app['log']->getMonolog()->getHandlers())->filter(function ($handler) use ($message, $level) {
                return $handler instanceof TestHandler
                    && $handler->hasRecordThatContains($message, $level);
            })->count() > 0;
    }
}