<?php
/*
 * This file is part of Aplus Framework Routing Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Routing\Debug;

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Routing\Debug\RoutingCollector;
use Framework\Routing\RouteCollection;
use Framework\Routing\Router;
use PHPUnit\Framework\TestCase;

final class RoutingCollectorTest extends TestCase
{
    protected RoutingCollector $collector;

    protected function setUp() : void
    {
        $this->collector = new RoutingCollector();
    }

    protected function setServerVars() : void
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/contact';
        $_SERVER['HTTP_HOST'] = 'domain.tld';
    }

    protected function makeRouter() : Router
    {
        $this->setServerVars();
        $router = new Router(new Response(new Request()));
        $router->setDebugCollector($this->collector);
        $router->serve('http://domain.tld', static function (RouteCollection $routes) : void {
            $routes->get('/', static fn () => 'Home page');
            $routes->get('/contact', static fn () => 'Contact page', 'contact');
            $routes->post('/contact', static fn () => 'Thanks!');
            $routes->notFound(static fn () => 'Page not found');
        })->serve('http://api.domain.tld', static function (RouteCollection $routes) : void {
            $routes->get('/users', static fn () => [[1], [2]], 'users');
        }, 'api');
        return $router;
    }

    public function testRouterNotSet() : void
    {
        $contents = $this->collector->getContents();
        self::assertStringContainsString('A Router instance has not been set', $contents);
    }

    public function testDefaultRouteNotFound() : void
    {
        $router = $this->makeRouter();
        $router->setDefaultRouteNotFound('App\Errors::notFound');
        $contents = $this->collector->getContents();
        self::assertStringContainsString('App\Errors::notFound', $contents);
    }

    public function testNoRouteCollection() : void
    {
        $this->setServerVars();
        $router = new Router(new Response(new Request()));
        $router->setDebugCollector($this->collector);
        $contents = $this->collector->getContents();
        self::assertStringContainsString('No route collection has been set', $contents);
    }

    public function testCollections() : void
    {
        $this->makeRouter();
        $activities = $this->collector->getActivities();
        self::assertSame('Serve route collection 1', $activities[0]['description']);
        self::assertSame('Serve route collection 2', $activities[1]['description']);
        $contents = $this->collector->getContents();
        self::assertStringContainsString('Route Collection 1', $contents);
        self::assertStringContainsString('Route Collection 2', $contents);
    }

    public function testNoMatchedRoute() : void
    {
        $this->makeRouter();
        $contents = $this->collector->getContents();
        self::assertStringContainsString('No matching route', $contents);
        self::assertStringNotContainsString('Time to Match', $contents);
    }

    public function testMatchedRoute() : void
    {
        $this->makeRouter()->match();
        $activities = $this->collector->getActivities();
        self::assertSame('Match route', $activities[2]['description']);
        $contents = $this->collector->getContents();
        self::assertStringNotContainsString('No matching route', $contents);
        self::assertStringContainsString('Time to Match', $contents);
    }

    public function testRun() : void
    {
        $this->makeRouter()->match()->run();
        $activities = $this->collector->getActivities();
        self::assertSame('Run matched route', $activities[3]['description']);
        $contents = $this->collector->getContents();
        self::assertStringNotContainsString('No matching route', $contents);
        self::assertStringContainsString('Time to Match', $contents);
    }

    public function testRunWithRouteActions() : void
    {
        $this->setServerVars();
        $_SERVER['REQUEST_URI'] = '/foo';
        $router = new Router(new Response(new Request()));
        $router->setDebugCollector($this->collector);
        $router->serve('http://domain.tld', static function (RouteCollection $routes) : void {
            $routes->get('/foo', 'Tests\Routing\Support\WithRouteActions::index', 'actions');
        });
        $router->match()->run();
        $activities = $this->collector->getActivities();
        self::assertSame('Serve route collection 1', $activities[0]['description']);
        self::assertSame('Match route', $activities[1]['description']);
        self::assertSame('Run matched route', $activities[2]['description']);
        $contents = $this->collector->getContents();
        self::assertStringNotContainsString('No matching route', $contents);
        self::assertStringContainsString('Time to Match', $contents);
        self::assertSame('actions', $router->getMatchedRoute()->getName());
    }

    public function testNotRoutesInCollection() : void
    {
        $this->makeRouter()->serve('http://admin.domain.tld', static function (RouteCollection $routes) : void {
        });
        $contents = $this->collector->getContents();
        self::assertStringContainsString('No route has been set in this collection', $contents);
    }

    public function testOnlyRouteNotFoundInCollection() : void
    {
        $this->makeRouter()->serve('http://admin.domain.tld', static function (RouteCollection $routes) : void {
            $routes->notFound('foo');
        })->match();
        $contents = $this->collector->getContents();
        self::assertStringContainsString('Only Route Not Found has been set in this collection', $contents);
    }
}
