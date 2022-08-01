<?php declare(strict_types=1);
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

/**
 * Class RouteNotFoundAttributeTest.
 *
 * @package routing
 */
final class RouteNotFoundAttributeTest extends TestCase
{
    public function testWithOrigins() : void
    {
        $class = UsersRouteActionsResource::class;
        $reflector = new Reflector($class);
        $routes = $reflector->getRoutesNotFound();
        self::assertContains([
            'origins' => [
                'http://api.domain.xyz',
                'http://domain.com',
            ],
            'action' => $class . '::notFoundWithOriginAttributes',
        ], $routes);
        self::assertContains([
            'origins' => [
                'http://foo.net',
            ],
            'action' => $class . '::notFoundWithOriginParam',
        ], $routes);
    }

    public function testWithoutOrigins() : void
    {
        $class = UsersRouteActionsPresenter::class;
        $reflector = new Reflector($class);
        $routes = $reflector->getRoutesNotFound();
        self::assertContains([
            'origins' => [],
            'action' => $class . '::notFound',
        ], $routes);
    }
}
