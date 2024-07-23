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

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Routing\Route;
use Framework\Routing\RouteActions;
use Framework\Routing\Router;
use Framework\Routing\RoutingException;
use PHPUnit\Framework\TestCase;
use Tests\Routing\Support\WithoutRouteActions;
use Tests\Routing\Support\WithRouteActions;

final class RouteTest extends TestCase
{
    protected Response $response;
    protected Route $route;
    protected Router $router;

    protected function setUp() : void
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'domain.tld';
        $_SERVER['REQUEST_URI'] = '/users/25';
        $this->response = new Response(new Request());
        $this->router = new Router($this->response);
        $this->route = new Route(
            $this->router,
            '{scheme}://domain.tld',
            '/users/{int}',
            static function (array $params, mixed ...$construct) {
                return \implode(', ', [...$params, ...$construct]);
            }
        );
    }

    public function testOrigin() : void
    {
        self::assertSame('{scheme}://domain.tld', $this->route->getOrigin());
        self::assertSame('https://domain.tld', $this->route->getOrigin('https'));
    }

    public function testUrl() : void
    {
        self::assertSame(
            '{scheme}://domain.tld/users/{int}',
            $this->route->getUrl()
        );
        self::assertSame(
            'http://domain.tld/users/{int}',
            $this->route->getUrl(['http'])
        );
        self::assertSame(
            '{scheme}://domain.tld/users/25',
            $this->route->getUrl([], [25])
        );
        self::assertSame(
            'http://domain.tld/users/25',
            $this->route->getUrl(['http'], ['25'])
        );
    }

    public function testOptions() : void
    {
        self::assertSame([], $this->route->getOptions());
        self::assertInstanceOf(Route::class, $this->route->setOptions(['foo' => 'bar']));
        self::assertSame(['foo' => 'bar'], $this->route->getOptions());
    }

    public function testName() : void
    {
        self::assertNull($this->route->getName());
        self::assertInstanceOf(Route::class, $this->route->setName('users.show'));
        self::assertSame('users.show', $this->route->getName());
    }

    public function testPath() : void
    {
        self::assertSame('/users/{int}', $this->route->getPath());
        self::assertSame('/users/10', $this->route->getPath(10)); // @phpstan-ignore-line
        self::assertInstanceOf(Route::class, $this->route->setPath('/u/{uuid}/show/{int}'));
        self::assertSame('/u/{uuid}/show/{int}', $this->route->getPath());
        self::assertSame(
            '/u/123e4567-e89b-12d3-a456-42661417400a/show/10',
            // @phpstan-ignore-next-line
            $this->route->getPath('123e4567-e89b-12d3-a456-42661417400a', 10)
        );
    }

    public function testAction() : void
    {
        self::assertInstanceOf(\Closure::class, $this->route->getAction());
        $action = static function () : void {
        };
        self::assertInstanceOf(Route::class, $this->route->setAction($action));
        self::assertSame($action, $this->route->getAction());
        self::assertInstanceOf(Route::class, $this->route->setAction('\Users::show/0'));
        self::assertSame('Users::show/0', $this->route->getAction());
    }

    public function testActionArguments() : void
    {
        self::assertSame([], $this->route->getActionArguments());
        self::assertInstanceOf(
            Route::class,
            $this->route->setActionArguments([
                0 => '10',
                2 => 'hello-world',
                1 => 'foo',
            ])
        );
        self::assertSame([
            0 => '10',
            1 => 'foo',
            2 => 'hello-world',
        ], $this->route->getActionArguments());
    }

    public function testActionArgumentsWithRandomValues() : void
    {
        $this->route->setAction(
            '\Tests\Routing\Support\WithRouteActions::index/abc/$1/$0/cde'
        )->setActionArguments([
            'zero',
            'one',
        ]);
        $response = $this->route->run();
        self::assertSame('abc, one, zero, cde', $response->getBody());
    }

    protected function assertsForRunWithAction(Route $route) : void
    {
        self::assertInstanceOf(Route::class, $route->setActionArguments(['foo', 'bar']));
        self::assertInstanceOf(Response::class, $route->run());
        self::assertSame('foo, bar', $this->response->getBody());
        $this->response->setBody('');
        self::assertInstanceOf(Route::class, $route->setActionArguments(['param1', 'param2']));
        self::assertInstanceOf(Response::class, $route->run('construct1', 'construct2'));
        self::assertSame('param1, param2, construct1, construct2', $this->response->getBody());
    }

    public function testRunWithClosureAsAction() : void
    {
        $this->assertsForRunWithAction($this->route);
    }

    public function testRunWithStringAsAction() : void
    {
        $route = new Route(
            $this->router,
            'http://domain.tld',
            '/',
            '\Tests\Routing\Support\WithRouteActions::index/$0/$1'
        );
        $this->assertsForRunWithAction($route);
    }

    public function testRunWithStringAsActionWithAsteriskWildcard() : void
    {
        $route = new Route(
            $this->router,
            'http://domain.tld',
            '/',
            '\Tests\Routing\Support\WithRouteActions::index/*'
        );
        $this->assertsForRunWithAction($route);
    }

    public function testRunWithClassNotExists() : void
    {
        $route = new Route($this->router, 'http://domain.tld', '/', 'UnknownClass');
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('Class does not exist: UnknownClass');
        $route->run();
    }

    public function testRunWithClassNotInstanceOfRouteActions() : void
    {
        $route = new Route($this->router, 'http://domain.tld', '/', WithoutRouteActions::class);
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage(
            'Class ' . WithoutRouteActions::class . ' is not an instance of ' . RouteActions::class
        );
        $route->run();
    }

    public function testRunWithClassActionMethodNotExists() : void
    {
        $route = new Route(
            $this->router,
            'http://domain.tld',
            '/',
            '\Tests\Routing\Support\WithRouteActions::foo'
        );
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage(
            'Class action method does not exist: Tests\Routing\Support\WithRouteActions::foo'
        );
        $route->run();
    }

    public function testRunWithActionArgumentAsteriskNotAlone() : void
    {
        $route = new Route(
            $this->router,
            'http://domain.tld',
            '/',
            '\Tests\Routing\Support\WithRouteActions::foo/$0/*'
        );
        $route->setActionArguments(['arg1', 'arg2']);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Action arguments can only contain an asterisk wildcard and must be passed alone, on unnamed route'
        );
        $route->run();
    }

    public function testRunWithActionArgumentIsNotNumeric() : void
    {
        $route = new Route(
            $this->router,
            'http://domain.tld',
            '/',
            '\Tests\Routing\Support\WithRouteActions::foo/$0/$a'
        );
        $route->setActionArguments(['arg1', 'arg2']);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid action argument: $a, on unnamed route'
        );
        $route->run();
    }

    public function testRunWithUndefinedActionArgument() : void
    {
        $route = new Route(
            $this->router,
            'http://domain.tld',
            '/',
            '\Tests\Routing\Support\WithRouteActions::foo/$0/$1/$2'
        );
        $route->setActionArguments(['arg1', 'arg2']);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Undefined action argument: $2, on unnamed route'
        );
        $route->run();
    }

    public function testResponseBodyPartWithNull() : void
    {
        $route = new Route(
            $this->router,
            'http://domain.tld',
            '/',
            static function () {
                return null;
            }
        );
        self::assertInstanceOf(Response::class, $route->run());
        self::assertSame('', $this->response->getBody());
    }

    public function testResponseBodyPartWithResponse() : void
    {
        $route = new Route(
            $this->router,
            'http://domain.tld',
            '/',
            static function ($params, $response) {
                return $response;
            }
        );
        self::assertInstanceOf(Response::class, $route->run($this->response));
        self::assertSame('', $this->response->getBody());
    }

    public function testResponseBodyPartWithScalar() : void
    {
        $route = new Route(
            $this->router,
            'http://domain.tld',
            '/',
            static function () {
                return 1.5;
            }
        );
        self::assertInstanceOf(Response::class, $route->run());
        self::assertSame('1.5', $this->response->getBody());
    }

    public function testResponseBodyPartWithStringable() : void
    {
        $route = new Route(
            $this->router,
            'http://domain.tld',
            '/',
            static function () {
                return new class() {
                    public function __toString() : string
                    {
                        return '__toString';
                    }
                };
            }
        );
        self::assertInstanceOf(Response::class, $route->run());
        self::assertSame('__toString', $this->response->getBody());
    }

    public function testResponseBodyPartWithJsonSerializable() : void
    {
        $data = ['id' => 1, 'name' => 'Natan'];
        $route = new Route(
            $this->router,
            'http://domain.tld',
            '/',
            static function () use ($data) {
                return $data;
            }
        );
        self::assertInstanceOf(Response::class, $route->run());
        self::assertSame(\json_encode($data), $this->response->getBody());
    }

    public function testResponseBodyPartWithInvalidResult() : void
    {
        $route = new Route(
            $this->router,
            'http://domain.tld',
            '/',
            static function () {
                return new \DateTime();
            }
        );
        $route->setName('result-error');
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage(
            "Invalid action return type 'DateTime', on named route 'result-error'"
        );
        $route->run();
    }

    public function testScalarTypesCoercion() : void
    {
        $route = new Route(
            $this->router,
            'http://domain.tld',
            '/',
            WithRouteActions::class . '::noStrictTypes/*'
        );
        $route->setActionArguments(['1.1', '1.1', '1.1', '1.1']);
        // We will suppress the error issued by calling the RouteActions class
        // and the action method `$class->{$method}(...$arguments)` within
        // Route::run().
        self::assertSame(
            '{"bool":true,"float":1.1,"int":1,"string":"1.1"}',
            @$route->run()->getBody()
        );
        self::assertSame(
            'Implicit conversion from float-string "1.1" to int loses precision',
            \error_get_last()['message']
        );
    }

    public function testJsonSerialize() : void
    {
        self::assertSame(
            '"{scheme}:\/\/domain.tld\/users\/{int}"',
            \json_encode($this->route)
        );
    }
}
