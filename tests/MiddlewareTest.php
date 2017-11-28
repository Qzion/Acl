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

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Qzion\Acl\Middlewares\RoleMiddleware;
use Qzion\Acl\Middlewares\PermissionMiddleware;
use Qzion\Acl\Exceptions\Unauthorized;

class MiddlewareTest extends TestCase
{
    /**
     * @var RoleMiddleware
     */
    protected $roleMiddleware;

    /**
     * @var PermissionMiddleware
     */
    protected $permissionMiddleware;

    public function setUp()
    {
        parent::setUp();


        $this->roleMiddleware = new RoleMiddleware($this->app);

        $this->permissionMiddleware = new PermissionMiddleware($this->app);
    }

    /**
     * Helper function to allow you to run middleware
     *
     * @param $middleware
     * @param $parameter
     *
     * @return mixed
     */
    protected function runMiddleware($middleware, $parameter)
    {
        try {
            return $middleware->handle(new Request(), function () {
                return (new Response())->setContent('<html></html>');
            }, $parameter)->status();
        } catch (UnauthorizedException $e) {
            return $e->getStatusCode();
        }
    }

    /**
     * Checks to see if a guest will get an exception thrown if route is protected by role middleware
     *
     * @throws Unauthorized
     *
     * @test
     */
    public function checks_that_an_exception_is_thrown_to_a_guest_trying_to_access_a_route_protected_by_role_middleware()
    {
        $this->expectException(Unauthorized::class);
        $this->assertEquals(
            $this->runMiddleware(
                $this->roleMiddleware, 'testRole'
            ), 403);
    }

    /**
     * Checks if logged in user can access a route with role middleware if they have correct role
     *
     * @test
     */
    public function checks_that_a_user_with_correct_role_can_access_route_with_route_middleware()
    {
        Auth::login($this->testUser);
        $this->testUser->assignRole('testRole');
        $this->assertEquals(
            $this->runMiddleware(
                $this->roleMiddleware, 'testRole'
            ), 200);
    }

    /**
     * Checks if user can acesss a route if they have one role in the middleware roles
     *
     * @test
     */
    public function checks_if_a_user_can_access_a_role_protected_route_with_only_one_role()
    {
        Auth::login($this->testUser);

        $this->testUser->assignRole('testRole');

        $this->assertEquals(
            $this->runMiddleware(
                $this->roleMiddleware, 'testRole|testRole2'
            ), 200);

        $this->assertEquals(
            $this->runMiddleware(
                $this->roleMiddleware, ['testRole2', 'testRole']
            ), 200);
    }

    /**
     * Checks that a user without role can not access a role protected route
     *
     * @throws Unauthorized
     *
     * @test
     */
    public function checks_that_an_exception_is_thrown_if_user_does_not_have_a_role_they_cant_access_role_protected_route()
    {
        Auth::login($this->testUser);

        $this->testUser->assignRole(['testRole']);

        $this->expectException(Unauthorized::class);
        $this->assertEquals(
            $this->runMiddleware(
                $this->roleMiddleware, 'testRole2'
            ), 403);
    }

    /**
     * Checks that a user with no roles can not access a role protected route
     *
     * @test
     */
    public function checks_that_an_exception_is_thrown_if_user_has_no_roles_they_can_not_access_role_protected_route() {
        Auth::login($this->testUser);

        $this->expectException(Unauthorized::class);

        $this->assertEquals(
            $this->runMiddleware(
                $this->roleMiddleware, 'testRole|testRole2'
            ), 403);
    }

    /**
     * Checks if an exception is thrown if a route is protected without any roles defined.
     *
     * @test
     */
    public function checks_that_an_exception_is_thrown_if_role_protected_route_does_not_specify_role()
    {
        Auth::login($this->testUser);

        $this->expectException(Unauthorized::class);

        $this->assertEquals(
            $this->runMiddleware(
                $this->roleMiddleware, ''
            ), 403);
    }


    /**
     * Checks to see if a guest will get an exception thrown if route is protected by permission middleware
     *
     * @throws Unauthorized
     *
     * @test
     */
    public function a_guest_cannot_access_a_route_protected_by_the_permission_middleware()
    {
        $this->expectException(Unauthorized::class);

        $this->assertEquals(
            $this->runMiddleware(
                $this->permissionMiddleware, 'edit-articles'
            ), 403);
    }




}