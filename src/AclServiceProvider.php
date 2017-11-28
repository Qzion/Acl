<?php
/**
 *  _____  _____  _                         _
 * |     ||__   ||_| ___  ___     ___  ___ | |_
 * |  |  ||   __|| || . ||   | _ |   || -_||  _|
 * |__  _||_____||_||___||_|_||_||_|_||___||_|
 *    |__| hello@qzion.net
 */

namespace Qzion\Acl;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Qzion\Acl\Commands\CreatePermission;
use Qzion\Acl\Commands\CreateRole;
use Qzion\Acl\Contracts\Role as RoleContract;
use Qzion\Acl\Contracts\Permission as PermissionContract;

class AclServiceProvider extends ServiceProvider
{
    public function boot(PermissionRegistrar $permissionLoader)
    {
        // Publish The config file to the main laravel app config.
        $this->publishes([
            __DIR__.'/../config/acl.php' => $this->app->configPath().'acl.php',
        ], 'config');

        // Publish the migration file to main laravel app Migrations.
        if (! class_exists('CreateAclTables')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__ . '/Database/Migrations/create_acl_tables.php.stub' => $this->app->databasePath()."/Migrations/{$timestamp}_create_acl_tables"
            ], 'Migrations');
        }

        // Setups the artisian commands
        if($this->app->runningInConsole()) {
            $this->commands([
                CreateRole::class,
                CreatePermission::class
            ]);
        }

        // Binds the models to the contracts
        $this->registerModelBindings();

        // Load all the permissions into system
        $permissionLoader->registerPermissions();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/acl.php', 'acl');

        $this->registerBladeExtensions();
    }

    /**
     * Binding the models to contracts.
     */
    protected function registerModelBindings()
    {
        $config = $this->app->config['acl.models'];

        $this->app->bind(PermissionContract::class, $config['permission']);
        $this->app->bind(RoleContract::class, $config['role']);
    }

    /**
     * Creates new blade directives for roles, no need for permissions as you have the can directive
     */
    protected function registerBladeExtensions()
    {
        $this->app->afterResolving('blade.compiler', function (BladeCompiler $bladeCompiler) {
            // @hasrole('admin') - Checks to see if a role exists
            $bladeCompiler->directive('role', function ($arguments) {
                list ($role, $guard) = explode(',', $arguments.',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasRole({$role})): ?>";
            });
            // @endhasrole - ends the has role directive
            $bladeCompiler->directive('endhasrole', function () { return '<?php endif; ?>'; });

            // @hasanyrole(['admin', 'user'] - Checks to see if user has any of the roles listed
            $bladeCompiler->directive('hasanyrole', function ($arguments) {
                list ($role, $guard) = explode(',', $arguments.',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasAnyRole({$role})): ?>";
            });
            // @endhasanyrole
            $bladeCompiler->directive('endhasanyrole', function () { return '<?php endif; ?>'; });

            // @hasallrole(['admin', 'user'] - Checks to see if user has all of the roles listed
            $bladeCompiler->directive('hasaallrole', function ($arguments) {
                list ($role, $guard) = explode(',', $arguments.',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasAnyRole({$role})): ?>";
            });
            // @endhasaallrole
            $bladeCompiler->directive('endhasaallrole', function () { return '<?php endif; ?>'; });
        });
    }
}