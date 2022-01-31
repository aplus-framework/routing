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
use Framework\Language\Language;
use Framework\Routing\Route;
use Framework\Routing\RouteCollection;
use Framework\Routing\Router;
use PHPUnit\Framework\TestCase;

/**
 * Class RouterTest.
 */
final class RouterTest extends TestCase
{
    protected Response $response;
    protected Router $router;

    protected function setUp() : void
    {
        $this->prepare([
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'domain.tld',
            'REQUEST_URI' => '/',
        ]);
    }

    /**
     * @param array<string,mixed> $server
     */
    protected function prepare(array $server = []) : void
    {
        foreach ($server as $key => $value) {
            $_SERVER[$key] = $value;
        }
        $this->response = new Response(new Request());
        $this->router = new Router($this->response);
        $this->router->serve('http://domain.tld', static function (RouteCollection $routes) : void {
            $routes->get('/', static function () : void {
            }, 'home');
            $routes->options('/', static function () : void {
            }, 'home.options');
            $routes->get('/contact', static function () : void {
            }, 'contact.index');
            $routes->post('/contact', static function () : void {
            }, 'contact.create');
            $routes->patch('/post/{int}', static function () : void {
            }, 'post.update');
        });
    }

    public function testMagicGetter() : void
    {
        self::assertNull($this->router->defaultRouteNotFound); // @phpstan-ignore-line
        $this->router->setDefaultRouteNotFound('Foo');
        self::assertSame('Foo', $this->router->defaultRouteNotFound); // @phpstan-ignore-line
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Property not exists: ' . $this->router::class . '::$foo');
        $this->router->foo; // @phpstan-ignore-line
    }

    public function testLanguage() : void
    {
        $directory = \realpath(__DIR__ . '/../src/Languages') . \DIRECTORY_SEPARATOR;
        self::assertContains($directory, $this->router->getLanguage()->getDirectories());
        $router = new Router($this->response, new Language('es'));
        self::assertContains($directory, $router->getLanguage()->getDirectories());
    }

    public function testDefaultRouteActionMethod() : void
    {
        self::assertSame('index', $this->router->getDefaultRouteActionMethod());
        $this->router->setDefaultRouteActionMethod('main');
        self::assertSame('main', $this->router->getDefaultRouteActionMethod());
    }

    /**
     * @runInSeparateProcess
     */
    public function testServeAutoDetectOrigin() : void
    {
        $this->prepare([
            'HTTPS' => 'on',
            'HTTP_HOST' => 'anything.tld:8080',
        ]);
        $this->router->serve(null, static function (RouteCollection $routes) : void {
        });
        $this->router->match();
        self::assertSame('https://anything.tld:8080', $this->router->getMatchedOrigin());
    }

    public function testServeWithPlaceholders() : void
    {
        $this->prepare([
            'HTTP_HOST' => 'admin.domain.tld:8088',
            'REQUEST_URI' => '/users/23',
        ]);
        $this->router->serve(
            '{scheme}://{subdomain}.domain.tld:{port}',
            static function (RouteCollection $routes) : void {
                $routes->get('/users/{int}', 'Admin\Users::show/0', 'admin.users.show');
            }
        );
        $this->router->match();
        self::assertSame('http://admin.domain.tld:8088', $this->router->getMatchedOrigin());
        self::assertSame(
            ['http', 'admin', '8088'],
            $this->router->getMatchedOriginArguments()
        );
        self::assertSame('/users/23', $this->router->getMatchedPath());
        self::assertSame(
            ['23'],
            $this->router->getMatchedPathArguments()
        );
        self::assertSame('admin.users.show', $this->router->getMatchedRoute()->getName());
    }

    public function testMatchHeadMethodEqualsGet() : void
    {
        $this->prepare([
            'REQUEST_METHOD' => 'HEAD',
        ]);
        self::assertSame('home', $this->router->match()->getName());
    }

    public function testMatchCollectionNullReturnDefaultRouteNotFound() : void
    {
        $this->prepare([
            'HTTP_HOST' => 'unknown.tld',
        ]);
        self::assertSame('not-found', $this->router->match()->getName());
        self::assertNull($this->router->getMatchedCollection());
    }

    public function testMatchCollectionReturnRouteNotFound() : void
    {
        $this->prepare([
            'HTTP_HOST' => 'known.tld',
        ]);
        $collection = null;
        $this->router->serve('http://known.tld', static function (RouteCollection $routes) use (&$collection) : void {
            $routes->notFound(static function () : void {
            });
            $collection = $routes;
        });
        self::assertSame('collection-not-found', $this->router->match()->getName());
        self::assertSame($collection, $this->router->getMatchedCollection());
    }

    public function testMatchedCollectionName() : void
    {
        $this->prepare([
            'HTTP_HOST' => 'known.tld',
        ]);
        $this->router->serve('http://known.tld', static function (RouteCollection $routes) : void {
            $routes->notFound(static function () : void {
            });
        }, 'foo');
        self::assertSame('foo.collection-not-found', $this->router->match()->getName());
        self::assertSame('foo', $this->router->getMatchedCollection()->name);
    }

    public function testMatchRoute() : void
    {
        $this->prepare([
            'REQUEST_METHOD' => 'PATCH',
            'REQUEST_URI' => '/post/25',
        ]);
        $this->router->match();
        self::assertSame('post.update', $this->router->getMatchedRoute()->getName());
        self::assertSame(['25'], $this->router->getMatchedRoute()->getActionArguments());
        self::assertInstanceOf(RouteCollection::class, $this->router->getMatchedCollection());
    }

    public function testGetMatchedUrl() : void
    {
        self::assertNull($this->router->getMatchedUrl());
        $this->router->match();
        self::assertSame('http://domain.tld/', $this->router->getMatchedUrl());
    }

    public function testOptionsRoute() : void
    {
        $this->prepare([
            'REQUEST_METHOD' => 'OPTIONS',
        ]);
        self::assertSame('home.options', $this->router->match()->getName());
        self::assertNull($this->response->getHeader('Allow'));
    }

    public function testAlternativeRouteWithAutoOptions() : void
    {
        $this->prepare([
            'REQUEST_METHOD' => 'OPTIONS',
            'REQUEST_URI' => '/contact',
        ]);
        $this->router->setAutoOptions();
        $route = $this->router->match();
        self::assertSame('auto-allow-200', $route->getName());
        $route->run();
        self::assertSame('200 OK', $this->response->getStatus());
        self::assertSame('GET, HEAD, OPTIONS, POST', $this->response->getHeader('Allow'));
    }

    public function testAlternativeRouteWithAutoOptionsNotFound() : void
    {
        $this->prepare([
            'REQUEST_METHOD' => 'OPTIONS',
            'REQUEST_URI' => '/unknown',
        ]);
        $this->router->setAutoOptions();
        $route = $this->router->match();
        self::assertSame('not-found', $route->getName());
        $route->run();
        self::assertSame('404 Not Found', $this->response->getStatus());
        self::assertNull($this->response->getHeader('Allow'));
    }

    public function testAlternativeRouteWithAutoMethods() : void
    {
        $this->prepare([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/contact',
        ]);
        $this->router->setAutoMethods();
        $route = $this->router->match();
        self::assertSame('auto-allow-405', $route->getName());
        $route->run();
        self::assertSame('405 Method Not Allowed', $this->response->getStatus());
        self::assertSame('GET, HEAD, POST', $this->response->getHeader('Allow'));
    }

    public function testAlternativeRouteWithAutoMethodsNotFound() : void
    {
        $this->prepare([
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/unknown',
        ]);
        $this->router->setAutoMethods();
        $route = $this->router->match();
        self::assertSame('not-found', $route->getName());
        $route->run();
        self::assertSame('404 Not Found', $this->response->getStatus());
        self::assertNull($this->response->getHeader('Allow'));
    }

    public function testGetRoutes() : void
    {
        $routes = $this->router->getRoutes();
        self::assertSame(['GET', 'OPTIONS', 'POST', 'PATCH'], \array_keys($routes));
        self::assertInstanceOf(Route::class, $routes['GET'][0]);
        self::assertInstanceOf(Route::class, $routes['OPTIONS'][0]);
    }

    public function testHasNamedRoutes() : void
    {
        self::assertTrue($this->router->hasNamedRoute('home'));
        self::assertTrue($this->router->hasNamedRoute('post.update'));
        self::assertFalse($this->router->hasNamedRoute('foo'));
    }

    public function testGetNamedRoutes() : void
    {
        self::assertInstanceOf(Route::class, $this->router->getNamedRoute('home'));
        self::assertInstanceOf(Route::class, $this->router->getNamedRoute('post.update'));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Named route not found: foo');
        $this->router->getNamedRoute('foo');
    }

    public function testPlaceholders() : void
    {
        $default = $this->router->getPlaceholders();
        $custom = [
            'a-b' => '([a-b]+)',
            'c-e' => '([c-e]+)',
        ];
        $this->router->addPlaceholder($custom);
        $this->router->addPlaceholder('f-h', '([f-h]+)');
        $custom['f-h'] = '([f-h]+)';
        $expected = [];
        foreach ($custom as $key => $placeholder) {
            $expected['{' . $key . '}'] = $placeholder;
        }
        $expected = \array_merge($default, $expected);
        self::assertSame($expected, $this->router->getPlaceholders());
    }

    public function testReplacePlaceholders() : void
    {
        $placeholders = '{alpha}/{alphanum}/{any}/{unknown}/{num}/{segment}/{int}/{md5}';
        $patterns = '([a-zA-Z]+)/([a-zA-Z0-9]+)/(.*)/{unknown}/([0-9]+)/([^/]+)/([0-9]{1,18}+)/([a-f0-9]{32}+)';
        $merged = '([a-zA-Z]+)/{alphanum}/(.*)/{unknown}/([0-9]+)/([^/]+)/([0-9]{1,18}+)/([a-f0-9]{32}+)';
        self::assertSame(
            $patterns,
            $this->router->replacePlaceholders($placeholders)
        );
        self::assertSame(
            $placeholders,
            $this->router->replacePlaceholders($patterns, true)
        );
        self::assertSame(
            $patterns,
            $this->router->replacePlaceholders($merged)
        );
        self::assertSame(
            $placeholders,
            $this->router->replacePlaceholders($merged, true)
        );
        $this->router->addPlaceholder('unknown', '([1-5])');
        self::assertSame(
            '([a-zA-Z]+)/([a-zA-Z0-9]+)/(.*)/([1-5])/([0-9]+)/([^/]+)/([0-9]{1,18}+)/([a-f0-9]{32}+)',
            $this->router->replacePlaceholders($placeholders)
        );
        self::assertSame(
            $placeholders,
            $this->router->replacePlaceholders($patterns, true)
        );
    }

    public function testFillPlaceholders() : void
    {
        self::assertSame(
            'http://s1.domain.tld/users/25',
            $this->router->fillPlaceholders(
                'http://s{num}.domain.tld/users/{num}',
                1, // @phpstan-ignore-line
                // @phpstan-ignore-next-line
                25
            )
        );
        self::assertSame(
            'http://domain.tld/a-pretty-title/abc123',
            $this->router->fillPlaceholders(
                'http://domain.tld/{segment}/{alphanum}',
                'a-pretty-title',
                'abc123'
            )
        );
        self::assertSame(
            'http://s1.domain.tld/users/30',
            $this->router->fillPlaceholders('http://s1.domain.tld/users/30')
        );
    }

    public function testFillPlaceholdersWithArgumentsNotRequired() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('String has no placeholders. Arguments not required');
        // @phpstan-ignore-next-line
        $this->router->fillPlaceholders('http://s1.domain.tld/users/30', 1, 25);
    }

    public function testFillPlaceholdersWithArgumentNotSet() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Placeholder argument is not set: 1');
        // @phpstan-ignore-next-line
        $this->router->fillPlaceholders('http://s{num}.domain-{alpha}.tld', 25);
    }

    public function testFillPlaceholdersWithArgumentIsInvalid() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Placeholder argument is invalid: 0');
        $this->router->fillPlaceholders('http://s{num}.domain.tld', 'abc');
    }

    public function testDefaultRouteNotFound() : void
    {
        $this->prepare([
            'REQUEST_URI' => '/not-found-slug',
        ]);
        self::assertStringContainsString(
            '<p>Page not found</p>',
            $this->router->match()->run()->getBody()
        );
        self::assertSame('404 Not Found', $this->response->getStatus());
    }

    public function testDefaultRouteNotFoundWithJson() : void
    {
        $this->prepare([
            'HTTP_CONTENT_TYPE' => 'application/json',
            'REQUEST_URI' => '/not-found-slug',
        ]);
        self::assertSame(
            \json_encode(['status' => ['code' => 404, 'reason' => 'Not Found']]),
            $this->router->match()->run()->getBody()
        );
        self::assertSame('404 Not Found', $this->response->getStatus());
    }

    public function testDefaultRouteNotFoundWithCustomAction() : void
    {
        $this->prepare([
            'REQUEST_URI' => '/not-found-slug',
        ]);
        $this->router->setDefaultRouteNotFound(static function () {
            return 'Default route not found';
        });
        self::assertSame(
            'Default route not found',
            $this->router->match()->run()->getBody()
        );
        self::assertSame('200 OK', $this->response->getStatus());
    }

    public function testRouteNotFound() : void
    {
        $this->router->match();
        self::assertSame('not-found', $this->router->getRouteNotFound()->getName());
        $this->router->getMatchedCollection()->notFound('foo');
        self::assertSame('collection-not-found', $this->router->getRouteNotFound()->getName());
    }

    public function testJsonSerialize() : void
    {
        $json = \json_encode($this->router);
        self::assertIsString($json);
        $json = \json_decode($json); // @phpstan-ignore-line
        self::assertNull($json->matched);
        self::assertIsArray($json->collections);
        self::assertIsBool($json->isAutoMethods);
        self::assertIsBool($json->isAutoOptions);
        self::assertArrayHasKey('{int}', (array) $json->placeholders);
    }

    /**
     * Test that some Router setters work.
     *
     * For some reason, as a performance improvement, they are not used
     * internally, because I preferred to set values directly on properties.
     *
     * Sometimes we just do what we need to do, as quickly as possible.
     */
    public function testSatisfaction() : void
    {
        $router = new class($this->response) extends Router {
            public function setMatchedCollection(RouteCollection $matchedCollection) : static
            {
                return parent::setMatchedCollection($matchedCollection);
            }

            public function setMatchedRoute(Route $route) : static
            {
                return parent::setMatchedRoute($route);
            }
        };
        $collection = new RouteCollection($router, 'http://upaupa.com');
        $router->setMatchedCollection($collection);
        self::assertSame($collection, $router->getMatchedCollection());
        $route = new Route($router, 'http://upaupa.com', '/', 'Home::index');
        $router->setMatchedRoute($route);
        self::assertSame($route, $router->getMatchedRoute());
    }
}
