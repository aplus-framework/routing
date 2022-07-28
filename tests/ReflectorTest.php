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
use Tests\Routing\Support\UsersRouteActionsPresenter;
use Tests\Routing\Support\UsersRouteActionsResource;
use Tests\Routing\Support\WithoutRouteActions;

/**
 * Class ReflectorTest.
 */
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
                'http://domain.com',
                'http://api.domain.xyz',
            ],
            'methods' => ['DELETE'],
            'path' => '/users/{int}',
            'arguments' => '0',
            'name' => 'users.delete',
            'action' => UsersRouteActionsResource::class . '::delete',
        ], $reflector->getRoutes());
    }
}
