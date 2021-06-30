<?php
/*
 * This file is part of The Framework Routing Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Routing;

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Routing\Collection;
use Framework\Routing\Route;
use Framework\Routing\Router;
use Framework\Routing\RoutingException;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
	protected Router $router;
	protected Response $response;

	public function setup() : void
	{
		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['HTTP_HOST'] = 'domain.tld:81';
		$_SERVER['REQUEST_URI'] = '/';
		$this->response = new Response(new Request());
		$this->router = new Router($this->response);
	}

	protected function prepare(array $server = []) : void
	{
		foreach ($server as $key => $value) {
			$_SERVER[$key] = $value;
		}
		$this->response = new Response(new Request());
		$this->router = new Router($this->response);
		$this->router->serve(
			'{scheme}://domain.tld:{num}',
			static function (Collection $collection) : void {
				$collection->get('/users/{num}', static function (array $params) {
					return "User page: {$params[0]}";
				});
				$collection->get('/users/{num}/posts/{num}', static function (array $params) {
					return "User {$params[0]}, post: {$params[1]}";
				})->setName('user.post');
				$collection->get('contact', static function () {
					return 'Contact page';
				}, 'ctt');
				$collection->get('', static function () {
					return 'Home page';
				})->setName('home');
				$collection->get('foo', 'Foo');
				$collection->get('bar', 'Tests\Routing\Support\Shop::bar');
				$collection->get('shop', 'Tests\Routing\Support\Shop');
				$collection->get('shop/products', 'Tests\Routing\Support\Shop::listProducts');
				$collection->get(
					'shop/products/{title}/{num}/([a-z]{2})',
					'Tests\Routing\Support\Shop::showProduct/1/0/2',
					'shop.showProduct'
				);
			}
		);
	}

	public function testEmptyOrigin() : void
	{
		$_SERVER['HTTPS'] = 'on';
		$_SERVER['HTTP_HOST'] = 'localhost:8080';
		$this->router->serve(null, static function (Collection $routes) : void {
			$routes->get('/', 'Home::index', 'home');
		});
		$this->assertEquals('Home::index', $this->router->getNamedRoute('home')->getAction());
	}

	public function testRouteActionParams() : void
	{
		$this->prepare();
		$route = $this->router->getNamedRoute('shop.showProduct');
		$this->assertEmpty($route->getActionParams());
		$route->setActionParams([1 => 25, 0 => 'hello-spirit', 2 => 'br']);
		$this->assertEquals(
			'/shop/products/{title}/{num}/([a-z]{2})',
			$route->getPath()
		);
		$this->assertEquals(
			'/shop/products/hello-spirit/25/br',
			$route->getPath(...$route->getActionParams())
		);
		$this->assertEquals(
			[25, 'hello-spirit', 'br'],
			$route->run()
		);
	}

	public function testRouteActionParamsEmpty() : void
	{
		$this->prepare();
		$route = $this->router->getNamedRoute('shop.showProduct');
		$route->setActionParams(['hello-spirit']);
		$this->expectExceptionMessage('Placeholder parameter is empty: 1');
		$route->getPath(...$route->getActionParams());
	}

	public function testRouteActionParamsInvalid() : void
	{
		$this->prepare();
		$route = $this->router->getNamedRoute('shop.showProduct');
		$route->setActionParams([0 => 'hello-spirit', 1 => 25, 2 => 'b1']);
		$this->expectExceptionMessage('Placeholder parameter is invalid: 2');
		$route->getPath(...$route->getActionParams());
	}

	public function testMatchHead() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'head',
		]);
		$this->assertEquals(
			'home',
			$this->router->match()->getName()
		);
	}

	public function testMatchedURL() : void
	{
		$this->prepare([
			'REQUEST_URI' => '/users/5/posts/12/?a=foo&e=5#id-x',
		]);
		$this->router->match();
		$this->assertEquals(
			'http://domain.tld:81/users/5/posts/12',
			$this->router->getMatchedURL()
		);
	}

	public function testMatchedOrigin() : void
	{
		$this->prepare([
			'REQUEST_URI' => '/users/5/posts/12',
		]);
		$this->router->match();
		$this->assertEquals('http://domain.tld:81', $this->router->getMatchedOrigin());
		$this->assertEquals(['http', 81], $this->router->getMatchedOriginParams());
	}

	public function testMatchedPath() : void
	{
		$this->prepare([
			'REQUEST_URI' => '/users/5/posts/12',
		]);
		$this->router->match();
		$this->assertEquals('/users/5/posts/12', $this->router->getMatchedPath());
		$this->assertEquals([5, 12], $this->router->getMatchedPathParams());
	}

	public function testMatchedRoute() : void
	{
		$this->prepare([
			'REQUEST_URI' => '/users/5/posts/12',
		]);
		$this->assertNull($this->router->getMatchedRoute());
		$this->router->match();
		$this->assertInstanceOf(Route::class, $this->router->getMatchedRoute());
		$this->assertEquals('user.post', $this->router->getMatchedRoute()->getName());
		$this->assertEquals(
			'/users/{num}/posts/{num}',
			$this->router->getMatchedRoute()->getPath()
		);
	}

	public function testRouteURL() : void
	{
		$this->prepare([
			'REQUEST_URI' => '/users/5/posts/12',
		]);
		$this->router->match();
		$this->assertEquals(
			'{scheme}://domain.tld:{num}',
			$this->router->getMatchedRoute()->getOrigin()
		);
		$this->assertEquals(
			'https://domain.tld:82',
			$this->router->getMatchedRoute()->getOrigin('https', 82)
		);
		$this->assertEquals(
			'{scheme}://domain.tld:{num}',
			$this->router->getMatchedRoute()->getOrigin()
		);
		$this->assertEquals(
			'/users/{num}/posts/{num}',
			$this->router->getMatchedRoute()->getPath()
		);
		$this->assertEquals(
			'/users/4/posts/5',
			$this->router->getMatchedRoute()->getPath(4, 5)
		);
		$this->assertEquals(
			'/users/{num}/posts/{num}',
			$this->router->getMatchedRoute()->getPath()
		);
		$this->assertEquals(
			'{scheme}://domain.tld:{num}/users/{num}/posts/{num}',
			$this->router->getMatchedRoute()->getURL()
		);
		$this->assertEquals(
			'http://domain.tld:83/users/1/posts/2',
			$this->router->getMatchedRoute()->getURL(['http', 83], [1, 2])
		);
	}

	public function testDefaultRouteNotFound() : void
	{
		$this->prepare([
			'HTTP_HOST' => 'site.org',
		]);
		$route = $this->router->match();
		$this->assertEquals('not-found', $route->getName());
		$route->run();
		$this->assertEquals(404, $this->response->getStatusCode());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCustomDefaultRouteNotFound() : void
	{
		$this->prepare([
			'HTTP_HOST' => 'site.org',
		]);
		$this->router->setDefaultRouteNotFound(static function () : void {
			\http_response_code(400);
		});
		$route = $this->router->match();
		$this->assertEquals('not-found', $route->getName());
		$route->run();
		$this->assertEquals(400, \http_response_code());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCollectionRouteNotFound() : void
	{
		$this->prepare([
			'HTTP_HOST' => 'site.org',
		]);
		$this->router->serve('http://site.org', static function (Collection $collection) : void {
			$collection->notFound(static function () : void {
				\http_response_code(402);
			});
		});
		$route = $this->router->match();
		$this->assertEquals('collection-not-found', $route->getName());
		$route->run();
		$this->assertEquals(402, \http_response_code());
	}

	public function testDefaultRouteActionMethod() : void
	{
		$this->prepare([
			'HTTP_HOST' => 'foo.com',
		]);
		$this->router->serve('http://foo.com', static function (Collection $collection) : void {
			$collection->get('/', 'Tests\Routing\Support\Shop');
		});
		$this->assertEquals(
			'Tests\Routing\Support\Shop::index',
			$this->router->match()->run()
		);
		$this->router->setDefaultRouteActionMethod('listProducts');
		$this->assertEquals(
			'Tests\Routing\Support\Shop::listProducts',
			$this->router->match()->run()
		);
	}

	public function testRouteRunWithClass() : void
	{
		$this->prepare([
			'REQUEST_URI' => '/shop',
		]);
		$this->assertEquals(
			'Tests\Routing\Support\Shop::index',
			$this->router->match()->run()
		);
		$this->prepare([
			'REQUEST_URI' => '/shop/products',
		]);
		$this->assertEquals(
			'Tests\Routing\Support\Shop::listProducts',
			$this->router->match()->run()
		);
		$this->prepare([
			'REQUEST_URI' => '/shop/products/foo-bar/22/en',
		]);
		$this->assertEquals(
			[22, 'foo-bar', 'en'],
			$this->router->match()->run()
		);
	}

	public function testRouteRunWithClassNotExists() : void
	{
		$this->prepare([
			'REQUEST_URI' => '/foo',
		]);
		$this->expectException(RoutingException::class);
		$this->expectExceptionMessage('Class not exists: Foo');
		$this->router->match()->run();
	}

	public function testRouteRunWithClassMethodNotExists() : void
	{
		$this->prepare([
			'REQUEST_URI' => '/bar',
		]);
		$this->expectException(RoutingException::class);
		$this->expectExceptionMessage('Class method not exists: Tests\Routing\Support\Shop::bar');
		$this->router->match()->run();
	}

	public function testRouteRunWithUndefinedActionParam() : void
	{
		$this->prepare([
			'REQUEST_URI' => '/shop/products/foo-bar/22/br',
		]);
		$route = $this->router->match();
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Undefined action parameter: 2');
		$route->setActionParams([22, 'foo-bar']);
		$route->run();
	}

	public function testRoutePath() : void
	{
		$this->prepare();
		$this->assertEquals(
			'/users/10/posts/20',
			$this->router->getNamedRoute('user.post')->getPath(10, 20)
		);
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Placeholder parameter is empty: 1');
		$this->router->getNamedRoute('user.post')->getPath(10);
	}

	public function testGroup() : void
	{
		$this->router->serve(
			'{scheme}://domain.tld:{num}',
			static function (Collection $collection) : void {
				$collection->group('animals', [
					$collection->get('', 'Animals::index', 'animals')->setOptions([
						'x' => 'foo',
						'y' => 'bar',
					]),
					$collection->get('cat', 'Animals::cat', 'animals.cat'),
					$collection->get('dog', 'Animals::dog', 'animals.dog')
						->setOptions(['y' => 'set']),
				], ['x' => 'xis']);
				$collection->group('users', [
					$collection->get('', 'Users::index', 'users')->setOptions([
						'x' => [
							0,
							2 => ['c'],
						],
					]),
					$collection->post('', 'Users::index', 'users.create'),
					$collection->get('{num}', 'Users::show/0', 'users.show'),
					$collection->group('{num}/panel', [
						$collection->get('', 'Panel::index', 'panel'),
						$collection->group('config', [
							$collection->get('update', 'Panel::config', 'panel.update'),
						]),
					]),
				], ['x' => ['a', 'b']]);
			}
		);
		$this->assertEquals('/animals', $this->router->getNamedRoute('animals')->getPath());
		$this->assertEquals(
			['x' => 'foo', 'y' => 'bar'],
			$this->router->getNamedRoute('animals')->getOptions()
		);
		$this->assertEquals('/animals/cat', $this->router->getNamedRoute('animals.cat')->getPath());
		$this->assertEquals(
			['x' => 'xis'],
			$this->router->getNamedRoute('animals.cat')->getOptions()
		);
		$this->assertEquals('/animals/dog', $this->router->getNamedRoute('animals.dog')->getPath());
		$this->assertEquals(
			['x' => 'xis', 'y' => 'set'],
			$this->router->getNamedRoute('animals.dog')->getOptions()
		);
		$this->assertEquals('/users', $this->router->getNamedRoute('users')->getPath());
		$this->assertEquals(
			['x' => [0, 'b', ['c']]],
			$this->router->getNamedRoute('users')->getOptions()
		);
		$this->assertEquals('/users', $this->router->getNamedRoute('users.create')->getPath());
		$this->assertEquals('/users/25', $this->router->getNamedRoute('users.show')->getPath(25));
		$this->assertEquals('/users/{num}/panel', $this->router->getNamedRoute('panel')->getPath());
		$this->assertEquals(
			'/users/{num}/panel/config/update',
			$this->router->getNamedRoute('panel.update')->getPath()
		);
	}

	public function testHTTPMethods() : void
	{
		$this->prepare([
			'HTTP_HOST' => 'methods.com',
		]);
		$this->router->serve(
			'http://methods.com',
			static function (Collection $collection) : void {
				$collection->get('/', 'Home::get');
				$collection->post('/', 'Home::post');
				$collection->put('/', 'Home::put');
				$collection->patch('/', 'Home::patch');
				$collection->delete('/', 'Home::delete');
			}
		);
		$this->assertEquals(
			'Home::get',
			$this->router->match()->getAction()
		);
		$this->prepare([
			'REQUEST_METHOD' => 'POST',
		]);
		$this->assertEquals(
			'Home::post',
			$this->router->match()->getAction()
		);
		$this->prepare([
			'REQUEST_METHOD' => 'PUT',
		]);
		$this->assertEquals(
			'Home::put',
			$this->router->match()->getAction()
		);
		$this->prepare([
			'REQUEST_METHOD' => 'PATCH',
		]);
		$this->assertEquals(
			'Home::patch',
			$this->router->match()->getAction()
		);
		$this->prepare([
			'REQUEST_METHOD' => 'DELETE',
		]);
		$this->assertEquals(
			'Home::delete',
			$this->router->match()->getAction()
		);
	}

	public function testServe() : void
	{
		$this->prepare();
		$route = $this->router->match('GET', 'https://domain.tld:8080/users/25');
		$this->assertInstanceOf(Route::class, $route);
		$this->assertEquals('/users/{num}', $route->getPath());
		$this->assertEquals([25], $route->getActionParams());
		$this->assertEquals('User page: 25', $route->run());
		$route = $this->router->match('GET', 'https://domain.tld:8080/users/10/posts/15');
		$this->assertInstanceOf(Route::class, $route);
		$this->assertEquals('/users/{num}/posts/{num}', $route->getPath());
		$this->assertEquals('/users/7/posts/8', $route->getPath(7, 8));
		$this->assertEquals([10, 15], $route->getActionParams());
		$this->assertEquals('User 10, post: 15', $route->run());
	}

	public function testNamedRoute() : void
	{
		$this->prepare();
		$this->assertEquals('/contact', $this->router->getNamedRoute('ctt')->getPath());
		$this->assertEquals('/', $this->router->getNamedRoute('home')->getPath());
		$this->assertTrue($this->router->hasNamedRoute('home'));
		$this->assertFalse($this->router->hasNamedRoute('unknown'));
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Named route not found: unknown');
		$this->router->getNamedRoute('unknown');
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
		$expected = \array_merge($expected, $default);
		$this->assertEquals($expected, $this->router->getPlaceholders());
	}

	public function testReplacePlaceholders() : void
	{
		$placeholders = '{alpha}/{alphanum}/{any}/{unknown}/{num}/{segment}/{int}/{md5}';
		$patterns = '([a-zA-Z]+)/([a-zA-Z0-9]+)/(.*)/{unknown}/([0-9]+)/([^/]+)/([0-9]{1,18}+)/([a-f0-9]{32}+)';
		$merged = '([a-zA-Z]+)/{alphanum}/(.*)/{unknown}/([0-9]+)/([^/]+)/([0-9]{1,18}+)/([a-f0-9]{32}+)';
		$this->assertEquals(
			$patterns,
			$this->router->replacePlaceholders($placeholders)
		);
		$this->assertEquals(
			$placeholders,
			$this->router->replacePlaceholders($patterns, true)
		);
		$this->assertEquals(
			$patterns,
			$this->router->replacePlaceholders($merged)
		);
		$this->assertEquals(
			$placeholders,
			$this->router->replacePlaceholders($merged, true)
		);
		$this->router->addPlaceholder('unknown', '([1-5])');
		$this->assertEquals(
			'([a-zA-Z]+)/([a-zA-Z0-9]+)/(.*)/([1-5])/([0-9]+)/([^/]+)/([0-9]{1,18}+)/([a-f0-9]{32}+)',
			$this->router->replacePlaceholders($placeholders)
		);
		$this->assertEquals(
			$placeholders,
			$this->router->replacePlaceholders($patterns, true)
		);
	}

	public function testFillPlaceholders() : void
	{
		$this->assertEquals(
			'http://s1.domain.tld/users/25',
			$this->router->fillPlaceholders(
				'http://s{num}.domain.tld/users/{num}',
				1,
				25
			)
		);
		$this->assertEquals(
			'http://domain.tld/a-pretty-title/abc123',
			$this->router->fillPlaceholders(
				'http://domain.tld/{segment}/{alphanum}',
				'a-pretty-title',
				'abc123'
			)
		);
		$this->assertEquals(
			'http://s1.domain.tld/users/30',
			$this->router->fillPlaceholders('http://s1.domain.tld/users/30')
		);
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('String has no placeholders. Parameters not required');
		$this->router->fillPlaceholders('http://s1.domain.tld/users/30', 1, 25);
	}

	public function testFillEmptyPlaceholders() : void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Placeholder parameter is empty: 1');
		$this->router->fillPlaceholders('http://s{num}.domain-{alpha}.tld', 25);
	}

	public function testFillInvalidPlaceholders() : void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Placeholder parameter is invalid: 0');
		$this->router->fillPlaceholders('http://s{num}.domain.tld', 'abc');
	}

	public function testCollectionMatchWithPlaceholders() : void
	{
		$this->router->serve(
			'http://subdomain.domain.tld:{port}',
			static function (Collection $collection) : void {
				$collection->get('/', 'port');
			}
		);
		$this->router->serve(
			'{scheme}://subdomain.domain.tld:8080',
			static function (Collection $collection) : void {
				$collection->get('/', 'scheme');
			}
		);
		$this->router->serve(
			'{scheme}://{subdomain}.domain.tld:{port}',
			static function (Collection $collection) : void {
				$collection->get('/', 'scheme-subdomain-port');
			}
		);
		$this->router->serve(
			'https://domain.tld',
			static function (Collection $collection) : void {
				$collection->get('/', 'none');
			}
		);
		$this->router->serve(
			'{any}',
			static function (Collection $collection) : void {
				$collection->get('/', 'any');
			}
		);
		$this->assertEquals(
			'any',
			$this->router->match('GET', 'http://example.com')->getAction()
		);
		$this->assertEquals(
			'none',
			$this->router->match('GET', 'https://domain.tld')->getAction()
		);
		$this->assertEquals(
			'scheme-subdomain-port',
			$this->router->match('GET', 'http://test.domain.tld:8081')->getAction()
		);
		$this->assertEquals(
			'scheme',
			$this->router->match('GET', 'https://subdomain.domain.tld:8080')->getAction()
		);
		$this->assertEquals(
			'port',
			$this->router->match('GET', 'http://subdomain.domain.tld:8081')->getAction()
		);
		$this->assertEquals(
			'any',
			$this->router->match('GET', 'http://foo.bar.example.com')->getAction()
		);
	}

	public function testAutoOptions() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'OPTIONS',
			'HTTP_HOST' => 'domain.tld',
			'REQUEST_URI' => '/users/25',
		]);
		$this->router->serve('http://domain.tld', static function (Collection $collection) : void {
			$collection->resource('users', 'Tests\Routing\Support\Users', 'users');
		});
		$this->router->setAutoOptions();
		$route = $this->router->match();
		$this->assertEquals('auto-allow-200', $route->getName());
		$route->run();
		$this->assertEquals(200, $this->response->getStatusCode());
		$this->assertEquals(
			'DELETE, GET, HEAD, OPTIONS, PATCH, PUT',
			$this->response->getHeader('Allow')
		);
	}

	public function testAutoOptionsNotFound() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'OPTIONS',
			'HTTP_HOST' => 'domain.tld',
			'REQUEST_URI' => '/unknown',
		]);
		$this->router->serve('http://domain.tld', static function (Collection $collection) : void {
			$collection->resource('users', 'Tests\Routing\Support\Users', 'users');
		});
		$this->router->setAutoOptions();
		$route = $this->router->match();
		$this->assertEquals('not-found', $route->getName());
		$route->run();
		$this->assertEquals(404, $this->response->getStatusCode());
		$this->assertNull($this->response->getHeader('Allow'));
	}

	public function testAutoOptionsDisabled() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'OPTIONS',
			'HTTP_HOST' => 'domain.tld',
			'REQUEST_URI' => '/users/25',
		]);
		$this->router->serve('http://domain.tld', static function (Collection $collection) : void {
			$collection->resource('users', 'Tests\Routing\Support\Users', 'users');
		});
		$this->router->setAutoOptions(false);
		$route = $this->router->match();
		$this->assertEquals('not-found', $route->getName());
		$route->run();
		$this->assertEquals(404, $this->response->getStatusCode());
		$this->assertNull($this->response->getHeader('Allow'));
	}

	public function testAutoOptionsWithOptionsRoute() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'OPTIONS',
			'HTTP_HOST' => 'domain.tld',
			'REQUEST_URI' => '/users/25',
		]);
		$response = $this->response;
		$this->router->serve(
			'http://domain.tld',
			static function (Collection $collection) use ($response) : void {
				$collection->resource('users', 'Tests\Routing\Support\Users', 'users');
				$collection->options('users/{num}', static function () use ($response) : void {
					$response->setHeader('Foo', 'bar');
				});
			}
		);
		$this->router->setAutoOptions();
		$route = $this->router->match();
		$this->assertNull($route->getName());
		$route->run();
		$this->assertNull($this->response->getHeader('Allow'));
		$this->assertEquals('bar', $this->response->getHeader('Foo'));
	}

	public function testAutoMethods() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'PUT',
			'HTTP_HOST' => 'domain.tld',
			'REQUEST_URI' => '/users',
		]);
		$this->router->serve('http://domain.tld', static function (Collection $collection) : void {
			$collection->resource('users', 'Tests\Routing\Support\Users', 'users');
		});
		$this->router->setAutoMethods();
		$route = $this->router->match();
		$this->assertEquals('auto-allow-405', $route->getName());
		$route->run();
		$this->assertEquals(405, $this->response->getStatusCode());
		$this->assertEquals('GET, HEAD, POST', $this->response->getHeader('Allow'));
	}

	public function testAutoMethodsNotFound() : void
	{
		$this->prepare([
			'HTTP_HOST' => 'domain.tld',
			'REQUEST_URI' => '/unknown',
		]);
		$this->router->serve('http://domain.tld', static function (Collection $collection) : void {
			$collection->resource('users', 'Tests\Routing\Support\Users', 'users');
		});
		$this->router->setAutoMethods();
		$route = $this->router->match();
		$this->assertEquals('not-found', $route->getName());
		$route->run();
		$this->assertEquals(404, $this->response->getStatusCode());
		$this->assertNull($this->response->getHeader('Allow'));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testAutoMethodsDisabled() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'PUT',
			'HTTP_HOST' => 'domain.tld',
			'REQUEST_URI' => '/users',
		]);
		$this->router->serve('http://domain.tld', static function (Collection $collection) : void {
			$collection->resource('users', 'Tests\Routing\Support\Users', 'users');
		});
		$this->router->setAutoMethods(false);
		$route = $this->router->match();
		$this->assertEquals('not-found', $route->getName());
		$route->run();
		$this->assertEquals(404, $this->response->getStatusCode());
		$this->assertNull($this->response->getHeader('Allow'));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testAutoMethodsWithAutoOptions() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'OPTIONS',
			'HTTP_HOST' => 'domain.tld',
			'REQUEST_URI' => '/users',
		]);
		$this->router->serve('http://domain.tld', static function (Collection $collection) : void {
			$collection->resource('users', 'Tests\Routing\Support\Users', 'users');
		});
		$this->router->setAutoOptions(true);
		$this->router->setAutoMethods(true);
		$route = $this->router->match();
		$this->assertEquals('auto-allow-200', $route->getName());
		$route->run();
		$this->assertEquals(200, $this->response->getStatusCode());
		$this->assertEquals('GET, HEAD, OPTIONS, POST', $this->response->getHeader('Allow'));
	}

	protected function assertPresenter() : void
	{
		$route = $this->router->match('get', 'http://domain.tld/admin/users');
		$this->assertEquals('admin.users.index', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::index', $route->run());
		$route = $this->router->match('post', 'http://domain.tld/admin/users');
		$this->assertEquals('admin.users.create', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::create', $route->run());
	}

	public function testPresenter() : void
	{
		$this->router->serve('http://domain.tld', static function (Collection $collection) : void {
			$collection->presenter('admin/users', 'Tests\Routing\Support\Users', 'admin.users');
			$collection->presenter(
				'admin/users',
				'Tests\Routing\Support\Users',
				'admin.users',
				['update']
			);
		});
		$this->assertPresenter();
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testRedirect() : void
	{
		$this->prepare([
			'HTTP_HOST' => 'domain.tld',
			'REQUEST_URI' => '/shop',
		]);
		$this->router->serve('http://domain.tld', static function (Collection $collection) : void {
			$collection->redirect('/shop', 'https://store.domain.tld/home', 301);
		});
		$this->router->match()->run();
		$this->assertEquals(
			'https://store.domain.tld/home',
			$this->response->getHeader('Location')
		);
		$this->assertEquals(301, $this->response->getStatusCode());
	}

	public function testRoutes() : void
	{
		$this->prepare();
		foreach ($this->router->getRoutes() as $routes) {
			foreach ($routes as $route) {
				$this->assertInstanceOf(Route::class, $route);
			}
		}
	}

	public function testCollectionNamespace() : void
	{
		$this->router->serve('http://foo.com', static function (Collection $collection) : void {
			$collection->namespace('App', [
				$collection->get('/', 'Home::index', 'home'),
				$collection->namespace('\Blog\Test\\', [
					$collection->group('/blog', [
						$collection->get('', 'Blog', 'blog'),
						$collection->get('{num}', 'Posts::show/0', 'post'),
						$collection->get('foo', static function () : void {
						}, 'foo'),
					]),
				]),
			]);
		});
		$this->assertEquals('App\Home::index', $this->router->getNamedRoute('home')->getAction());
		$this->assertEquals(
			'App\Blog\Test\Blog',
			$this->router->getNamedRoute('blog')->getAction()
		);
		$this->assertEquals(
			'App\Blog\Test\Posts::show/0',
			$this->router->getNamedRoute('post')->getAction()
		);
		$this->assertIsCallable($this->router->getNamedRoute('foo')->getAction());
	}

	public function testCollectionMagicMethods() : void
	{
		$this->router->serve('http://foo.com', function (Collection $collection) : void {
			$this->assertEquals('http://foo.com', $collection->origin);
			$this->assertEquals($this->router, $collection->router);
			$this->assertIsArray($collection->routes);
			$this->assertNull($collection->getRouteNotFound());
			$collection->notFound('NotFound::index');
		});
		$this->router->match('get', 'http://foo.com/bla');
		$this->assertEquals('NotFound::index', $this->router->getMatchedRoute()->getAction());
	}

	public function testCollectionMagicMethodNotAllowed() : void
	{
		$this->expectException(\BadMethodCallException::class);
		$this->expectExceptionMessage(
			'Method not allowed: setOrigin'
		);
		$this->router->serve('http://foo.com', static function (Collection $collection) : void {
			$collection->setOrigin('foo');
		});
	}

	public function testCollectionMagicMethodNotFound() : void
	{
		$this->expectException(\BadMethodCallException::class);
		$this->expectExceptionMessage(
			'Method not found: foo'
		);
		$this->router->serve('http://foo.com', static function (Collection $collection) : void {
			$collection->foo();
		});
	}

	public function testCollectionMagicPropertyNotAllowed() : void
	{
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('Property not allowed: namespace');
		$this->router->serve('http://foo.com', static function (Collection $collection) : void {
			$collection->namespace;
		});
	}

	public function testCollectionMagicPropertyNotFound() : void
	{
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('Property not found: foo');
		$this->router->serve('http://foo.com', static function (Collection $collection) : void {
			$collection->foo;
		});
	}

	public function testBeforeAndAfterRouteActions() : void
	{
		$this->router->serve('http://foo.com', static function (Collection $collection) : void {
			$collection->get('/before', 'Tests\Routing\Support\BeforeActionRoute::index');
			$collection->get('/after', 'Tests\Routing\Support\AfterActionRoute::index');
		});
		$this->assertEquals(
			'Tests\Routing\Support\BeforeActionRoute::beforeAction',
			$this->router->match('GET', 'http://foo.com/before')->run()
		);
		$this->assertEquals(
			'Tests\Routing\Support\AfterActionRoute::afterAction',
			$this->router->match('GET', 'http://foo.com/after')->run()
		);
	}
}
