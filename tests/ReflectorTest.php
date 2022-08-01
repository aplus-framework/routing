<?php
/*
 * This file is part of Aplus Framework Routing Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Routing;

use Framework\Routing\Reflector;
use PHPUnit\Framework\TestCase;
use Tests\Routing\Support\ChildClass;
use Tests\Routing\Support\UsersRouteActionsPresenter;
use Tests\Routing\Support\UsersRouteActionsResource;
use Tests\Routing\Support\WithoutRouteActions;

final class ReflectorTest extends TestCase
{
    public function testWithoutRouteActions() : void
    {
        $reflector = new Reflector(new WithoutRouteActions());
        self::assertSame([], $reflector->getRoutes());
    }

    public function testWithoutOrigins() : void
    {
        $reflector = new Reflector(new UsersRouteActionsPresenter());
        self::assertContains([
            'origins' => [],
            'methods' => ['GET'],
            'path' => '/users',
            'arguments' => '*',
            'name' => null,
            'action' => UsersRouteActionsPresenter::class . '::index',
        ], $reflector->getRoutes());
        self::assertContains([
            'origins' => [],
            'methods' => ['POST'],
            'path' => '/users',
            'arguments' => '*',
            'name' => null,
            'action' => UsersRouteActionsPresenter::class . '::create',
        ], $reflector->getRoutes());
        self::assertContains([
            'origins' => [],
            'methods' => ['PATCH'],
            'path' => '/users',
            'arguments' => '*',
            'name' => 'repeated',
            'action' => UsersRouteActionsPresenter::class . '::create',
        ], $reflector->getRoutes());
        self::assertContains([
            'origins' => [],
            'methods' => ['POST'],
            'path' => '/users/{int}/delete',
            'arguments' => '*',
            'name' => null,
            'action' => UsersRouteActionsPresenter::class . '::delete',
        ], $reflector->getRoutes());
    }

    public function testWithOrigins() : void
    {
        $reflector = new Reflector(UsersRouteActionsResource::class);
        self::assertContains([
            'origins' => [
                'http://foo.com',
            ],
            'methods' => ['GET'],
            'path' => '/users',
            'arguments' => '*',
            'name' => null,
            'action' => UsersRouteActionsResource::class . '::index',
        ], $reflector->getRoutes());
        self::assertContains([
            'origins' => [
                'http://api.domain.xyz',
                'http://domain.com',
            ],
            'methods' => ['DELETE'],
            'path' => '/users/{int}',
            'arguments' => '0',
            'name' => 'users.delete',
            'action' => UsersRouteActionsResource::class . '::delete',
        ], $reflector->getRoutes());
    }

    public function testInChildClass() : void
    {
        $reflector = new Reflector(new ChildClass());
        self::assertContains([
            'origins' => [
                'http://bar.xyz',
            ],
            'methods' => ['GET'],
            'path' => '/hello',
            'arguments' => '*',
            'name' => null,
            'action' => ChildClass::class . '::hello',
        ], $reflector->getRoutes());
        self::assertContains([
            'origins' => [
                'xxx',
            ],
            'methods' => ['GET'],
            'path' => '/replace-origin',
            'arguments' => '*',
            'name' => null,
            'action' => ChildClass::class . '::replaceOrigin',
        ], $reflector->getRoutes());
        self::assertContains([
            'origins' => [
                'http://bar.xyz',
            ],
            'methods' => ['GET'],
            'path' => '/bye',
            'arguments' => '*',
            'name' => null,
            'action' => ChildClass::class . '::bye',
        ], $reflector->getRoutes());
    }
}
