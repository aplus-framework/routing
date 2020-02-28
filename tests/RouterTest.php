<?php namespace Tests\Routing;

use Framework\Routing\Collection;
use Framework\Routing\Exception;
use Framework\Routing\Route;
use Framework\Routing\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
	protected Router $router;

	public function setup() : void
	{
		$this->router = new Router();
	}

	protected function prepare()
	{
		$this->router->serve('{scheme}://domain.tld:{num}', function (Collection $collection) {
			$collection->get('/users/{num}', function (array $params) {
				return "User page: {$params[0]}";
			});
			$collection->get('/users/{num}/posts/{num}', function (array $params) {
				return "User {$params[0]}, post: {$params[1]}";
			})->setName('user.post');
			$collection->get('contact', function () {
				return 'Contact page';
			}, 'ctt');
			$collection->get('', function () {
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
		});
	}

	public function testRouteActionParams()
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

	public function testRouteActionParamsEmpty()
	{
		$this->prepare();
		$route = $this->router->getNamedRoute('shop.showProduct');
		$route->setActionParams(['hello-spirit']);
		$this->expectExceptionMessage('Placeholder parameter is empty: 1');
		$route->getPath(...$route->getActionParams());
	}

	public function testRouteActionParamsInvalid()
	{
		$this->prepare();
		$route = $this->router->getNamedRoute('shop.showProduct');
		$route->setActionParams([0 => 'hello-spirit', 1 => 25, 2 => 'b1']);
		$this->expectExceptionMessage('Placeholder parameter is invalid: 2');
		$route->getPath(...$route->getActionParams());
	}

	public function testMatchHead()
	{
		$this->prepare();
		$this->assertEquals(
			'home',
			$this->router->match('head', 'http://domain.tld:81')->getName()
		);
	}

	public function testMatchedURL()
	{
		$this->prepare();
		$this->router->match('get', 'http://domain.tld:81/users/5/posts/12/?a=foo&e=5#id-x');
		$this->assertEquals(
			'http://domain.tld:81/users/5/posts/12',
			$this->router->getMatchedURL()
		);
	}

	public function testMatchedOrigin()
	{
		$this->prepare();
		$this->router->match('get', 'http://domain.tld:81/users/5/posts/12');
		$this->assertEquals('http://domain.tld:81', $this->router->getMatchedOrigin());
		$this->assertEquals(['http', 81], $this->router->getMatchedOriginParams());
	}

	public function testMatchedPath()
	{
		$this->prepare();
		$this->router->match('get', 'http://domain.tld:81/users/5/posts/12');
		$this->assertEquals('/users/5/posts/12', $this->router->getMatchedPath());
		$this->assertEquals([5, 12], $this->router->getMatchedPathParams());
	}

	public function testMatchedRoute()
	{
		$this->prepare();
		$this->assertNull($this->router->getMatchedRoute());
		$this->router->match('get', 'http://domain.tld:81/users/5/posts/12');
		$this->assertInstanceOf(Route::class, $this->router->getMatchedRoute());
		$this->assertEquals('user.post', $this->router->getMatchedRoute()->getName());
		$this->assertEquals(
			'/users/{num}/posts/{num}',
			$this->router->getMatchedRoute()->getPath()
		);
	}

	public function testRouteURL()
	{
		$this->prepare();
		$this->router->match('get', 'http://domain.tld:81/users/5/posts/12');
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

	/**
	 * @runInSeparateProcess
	 */
	public function testDefaultRouteNotFound()
	{
		$route = $this->router->match('GET', 'http://site.org');
		$this->assertEquals('not-found', $route->getName());
		$route->run();
		$this->assertEquals(404, \http_response_code());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCustomDefaultRouteNotFound()
	{
		$this->router->setDefaultRouteNotFound(function () {
			\http_response_code(400);
		});
		$route = $this->router->match('GET', 'http://site.org');
		$this->assertEquals('not-found', $route->getName());
		$route->run();
		$this->assertEquals(400, \http_response_code());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCollectionRouteNotFound()
	{
		$this->router->serve('http://site.org', function (Collection $collection) {
			$collection->notFound(function () {
				\http_response_code(402);
			});
		});
		$route = $this->router->match('GET', 'http://site.org');
		$this->assertEquals('collection-not-found', $route->getName());
		$route->run();
		$this->assertEquals(402, \http_response_code());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testDefaultRouteActionMethod()
	{
		$this->router->serve('http://foo.com', function (Collection $collection) {
			$collection->get('/', 'Tests\Routing\Support\Shop');
		});
		$this->assertEquals(
			'Tests\Routing\Support\Shop::index',
			$this->router->match('get', 'http://foo.com')->run()
		);
		$this->router->setDefaultRouteActionMethod('listProducts');
		$this->assertEquals(
			'Tests\Routing\Support\Shop::listProducts',
			$this->router->match('get', 'http://foo.com')->run()
		);
	}

	public function testRouteRunWithClass()
	{
		$this->prepare();
		$this->assertEquals(
			'Tests\Routing\Support\Shop::index',
			$this->router->match('GET', 'https://domain.tld:8081/shop')->run()
		);
		$this->assertEquals(
			'Tests\Routing\Support\Shop::listProducts',
			$this->router->match('GET', 'https://domain.tld:8081/shop/products')->run()
		);
		$this->assertEquals(
			[22, 'foo-bar', 'en'],
			$this->router->match('GET', 'https://domain.tld:8081/shop/products/foo-bar/22/en')
				->run()
		);
	}

	public function testRouteRunWithClassNotExists()
	{
		$this->prepare();
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Class not exists: Foo');
		$this->router->match('GET', 'https://domain.tld:8081/foo')->run();
	}

	public function testRouteRunWithClassMethodNotExists()
	{
		$this->prepare();
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Class method not exists: Tests\Routing\Support\Shop::bar');
		$this->router->match('GET', 'https://domain.tld:8081/bar')->run();
	}

	public function testRouteRunWithUndefinedActionParam()
	{
		$this->prepare();
		$route = $this->router->match('GET', 'https://domain.tld:8081/shop/products/foo-bar/22/br');
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Undefined action parameter: 2');
		$route->setActionParams([22, 'foo-bar']);
		$route->run();
	}

	public function testRoutePath()
	{
		$this->prepare();
		$this->assertEquals(
			'/users/10/posts/20',
			$this->router->getNamedRoute('user.post')->getPath(10, 20)
		);
		$this->expectException(\InvalidArgumentException::class);
		$this->router->getNamedRoute('user.post')->getPath(10);
	}

	public function testGroup()
	{
		$this->router->serve('{scheme}://domain.tld:{num}', function (Collection $collection) {
			$collection->group('animals', [
				$collection->get('', 'Animals::index', 'animals')->setOptions([
					'x' => 'foo',
					'y' => 'bar',
				]),
				$collection->get('cat', 'Animals::cat', 'animals.cat'),
				$collection->get('dog', 'Animals::dog', 'animals.dog')->setOptions(['y' => 'set']),
			], ['x' => 'xis']);
			$collection->group('users', [
				$collection->get('', 'Users::index', 'users')->setOptions(['x' => [0, 2 => ['c']]]),
				$collection->post('', 'Users::index', 'users.create'),
				$collection->get('{num}', 'Users::show/0', 'users.show'),
				$collection->group('{num}/panel', [
					$collection->get('', 'Panel::index', 'panel'),
					$collection->group('config', [
						$collection->get('update', 'Panel::config', 'panel.update'),
					]),
				]),
			], ['x' => ['a', 'b']]);
		});
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

	public function testHTTPMethods()
	{
		$this->router->serve('{scheme}://domain.tld:{num}', function (Collection $collection) {
			$collection->get('/', 'Home::get');
			$collection->post('/', 'Home::post');
			$collection->put('/', 'Home::put');
			$collection->patch('/', 'Home::patch');
			$collection->delete('/', 'Home::delete');
		});
		$base_url = 'http://domain.tld:8080';
		$this->assertEquals(
			'Home::get',
			$this->router->match('GET', $base_url)->getAction()
		);
		$this->assertEquals(
			'Home::post',
			$this->router->match('POST', $base_url)->getAction()
		);
		$this->assertEquals(
			'Home::put',
			$this->router->match('PUT', $base_url)->getAction()
		);
		$this->assertEquals(
			'Home::patch',
			$this->router->match('PATCH', $base_url)->getAction()
		);
		$this->assertEquals(
			'Home::delete',
			$this->router->match('DELETE', $base_url)->getAction()
		);
	}

	public function testServe()
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

	/**
	 * @runInSeparateProcess
	 */
	public function testValidateHTTPMethod()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->router->match('FOO', 'http://domain.tld:8080');
	}

	public function testValidateURLWithoutScheme()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->router->match('GET', 'domain.tld:8080');
	}

	public function testValidateURLWithoutSchemeOnlySlashs()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->router->match('GET', '//domain.tld:8080');
	}

	public function testNamedRoute()
	{
		$this->prepare();
		$this->assertEquals('/contact', $this->router->getNamedRoute('ctt')->getPath());
		$this->assertEquals('/', $this->router->getNamedRoute('home')->getPath());
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Named route not found: unknown');
		$this->router->getNamedRoute('unknown');
	}

	public function testPlaceholders()
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

	public function testReplacePlaceholders()
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

	public function testFillPlaceholders()
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
		$this->router->fillPlaceholders('http://s1.domain.tld/users/30', 1, 25);
	}

	public function testFillEmptyPlaceholders()
	{
		$this->expectException(\Exception::class);
		$this->router->fillPlaceholders('http://s{num}.domain-{alpha}.tld', 25);
	}

	public function testFillInvalidPlaceholders()
	{
		$this->expectException(\Exception::class);
		$this->router->fillPlaceholders('http://s{num}.domain.tld', 'abc');
	}

	public function testCollectionMatchWithPlaceholders()
	{
		$this->router->serve(
			'http://subdomain.domain.tld:{port}',
			function (Collection $collection) {
				$collection->get('/', 'port');
			}
		);
		$this->router->serve(
			'{scheme}://subdomain.domain.tld:8080',
			function (Collection $collection) {
				$collection->get('/', 'scheme');
			}
		);
		$this->router->serve(
			'{scheme}://{subdomain}.domain.tld:{port}',
			function (Collection $collection) {
				$collection->get('/', 'scheme-subdomain-port');
			}
		);
		$this->router->serve(
			'https://domain.tld',
			function (Collection $collection) {
				$collection->get('/', 'none');
			}
		);
		$this->router->serve(
			'{any}',
			function (Collection $collection) {
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

	/**
	 * @runInSeparateProcess
	 */
	public function testAutoOptions()
	{
		$this->router->serve('http://domain.tld', function (Collection $collection) {
			$collection->resource('users', 'Tests\Routing\Support\Users', 'users');
		});
		$this->router->setAutoOptions(true);
		$route = $this->router->match('options', 'http://domain.tld/users/25');
		$this->assertEquals('auto-allow-200', $route->getName());
		$route->run();
		$this->assertEquals(200, \http_response_code());
		$this->assertContains(
			'Allow: DELETE, GET, HEAD, OPTIONS, PATCH, PUT',
			xdebug_get_headers()
		);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testAutoOptionsNotFound()
	{
		$this->router->serve('http://domain.tld', function (Collection $collection) {
			$collection->resource('users', 'Tests\Routing\Support\Users', 'users');
		});
		$this->router->setAutoOptions(true);
		$route = $this->router->match('options', 'http://domain.tld/unknown');
		$this->assertEquals('not-found', $route->getName());
		$route->run();
		$this->assertEquals(404, \http_response_code());
		$this->assertNotContains(
			'Allow: DELETE, GET, HEAD, OPTIONS, PATCH, PUT',
			xdebug_get_headers()
		);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testAutoOptionsDisabled()
	{
		$this->router->serve('http://domain.tld', function (Collection $collection) {
			$collection->resource('users', 'Tests\Routing\Support\Users', 'users');
		});
		$this->router->setAutoOptions(false);
		$route = $this->router->match('options', 'http://domain.tld/users/25');
		$this->assertEquals('not-found', $route->getName());
		$route->run();
		$this->assertEquals(404, \http_response_code());
		$this->assertNotContains(
			'Allow: DELETE, GET, HEAD, OPTIONS, PATCH, PUT',
			xdebug_get_headers()
		);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testAutoOptionsWithOptionsRoute()
	{
		$this->router->serve('http://domain.tld', function (Collection $collection) {
			$collection->resource('users', 'Tests\Routing\Support\Users', 'users');
			$collection->options('users/{num}', function () {
				\header('Foo: bar');
			});
		});
		$this->router->setAutoOptions(true);
		$route = $this->router->match('options', 'http://domain.tld/users/25');
		$this->assertNull($route->getName());
		$route->run();
		$this->assertNotContains(
			'Allow: DELETE, GET, HEAD, OPTIONS, PATCH, PUT',
			xdebug_get_headers()
		);
		$this->assertContains(
			'Foo: bar',
			xdebug_get_headers()
		);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testAutoMethods()
	{
		$this->router->serve('http://domain.tld', function (Collection $collection) {
			$collection->resource('users', 'Tests\Routing\Support\Users', 'users');
		});
		$this->router->setAutoMethods(true);
		$route = $this->router->match('put', 'http://domain.tld/users');
		$this->assertEquals('auto-allow-405', $route->getName());
		$route->run();
		$this->assertEquals(405, \http_response_code());
		$this->assertContains(
			'Allow: GET, HEAD, POST',
			xdebug_get_headers()
		);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testAutoMethodsNotFound()
	{
		$this->router->serve('http://domain.tld', function (Collection $collection) {
			$collection->resource('users', 'Tests\Routing\Support\Users', 'users');
		});
		$this->router->setAutoMethods(true);
		$route = $this->router->match('get', 'http://domain.tld/unknown');
		$this->assertEquals('not-found', $route->getName());
		$route->run();
		$this->assertEquals(404, \http_response_code());
		$this->assertNotContains(
			'Allow: GET, HEAD, PUT',
			xdebug_get_headers()
		);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testAutoMethodsDisabled()
	{
		$this->router->serve('http://domain.tld', function (Collection $collection) {
			$collection->resource('users', 'Tests\Routing\Support\Users', 'users');
		});
		$this->router->setAutoMethods(false);
		$route = $this->router->match('put', 'http://domain.tld/users');
		$this->assertEquals('not-found', $route->getName());
		$route->run();
		$this->assertEquals(404, \http_response_code());
		$this->assertNotContains(
			'Allow: GET, HEAD, POST',
			xdebug_get_headers()
		);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testAutoMethodsWithAutoOptions()
	{
		$this->router->serve('http://domain.tld', function (Collection $collection) {
			$collection->resource('users', 'Tests\Routing\Support\Users', 'users');
		});
		$this->router->setAutoOptions(true);
		$this->router->setAutoMethods(true);
		$route = $this->router->match('options', 'http://domain.tld/users');
		$this->assertEquals('auto-allow-200', $route->getName());
		$route->run();
		$this->assertEquals(200, \http_response_code());
		$this->assertContains(
			'Allow: GET, HEAD, OPTIONS, POST',
			xdebug_get_headers()
		);
	}

	protected function assertResource()
	{
		$route = $this->router->match('get', 'http://domain.tld/users');
		$this->assertEquals('users.index', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::index', $route->run());
		$route = $this->router->match('post', 'http://domain.tld/users');
		$this->assertEquals('users.create', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::create', $route->run());
		$route = $this->router->match('get', 'http://domain.tld/users/25');
		$this->assertEquals('users.show', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::show/25', $route->run());
		$route = $this->router->match('patch', 'http://domain.tld/users/25');
		$this->assertEquals('users.update', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::update/25', $route->run());
		$route = $this->router->match('put', 'http://domain.tld/users/25');
		$this->assertEquals('users.replace', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::replace/25', $route->run());
		$route = $this->router->match('delete', 'http://domain.tld/users/25');
		$this->assertEquals('users.delete', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::delete/25', $route->run());
	}

	protected function assertResourceWithExcept()
	{
		$route = $this->router->match('get', 'http://domain.tld/users');
		$this->assertEquals('users.index', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::index', $route->run());
		$route = $this->router->match('post', 'http://domain.tld/users');
		$this->assertEquals('not-found', $route->getName());
		$this->assertNull($route->run());
		$route = $this->router->match('get', 'http://domain.tld/users/25');
		$this->assertEquals('not-found', $route->getName());
		$this->assertNull($route->run());
		$route = $this->router->match('patch', 'http://domain.tld/users/25');
		$this->assertEquals('users.update', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::update/25', $route->run());
		$route = $this->router->match('put', 'http://domain.tld/users/25');
		$this->assertEquals('not-found', $route->getName());
		$this->assertNull($route->run());
		$route = $this->router->match('delete', 'http://domain.tld/users/25');
		$this->assertEquals('users.delete', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::delete/25', $route->run());
	}

	public function testResource()
	{
		$this->router->serve('http://domain.tld', function (Collection $collection) {
			$collection->resource('users', 'Tests\Routing\Support\Users', 'users');
		});
		$this->assertResource();
	}

	public function testResourceWithExcept()
	{
		$this->router->serve('http://domain.tld', function (Collection $collection) {
			$collection->resource(
				'users',
				'Tests\Routing\Support\Users',
				'users',
				['create', 'show', 'replace']
			);
		});
		$this->assertResourceWithExcept();
	}

	public function testWebResource()
	{
		$this->router->serve('http://domain.tld', function (Collection $collection) {
			$collection->webResource('users', 'Tests\Routing\Support\Users', 'users');
		});
		$this->assertResource();
		$route = $this->router->match('get', 'http://domain.tld/users/new');
		$this->assertEquals('users.web_new', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::new', $route->run());
		$route = $this->router->match('get', 'http://domain.tld/users/25/edit');
		$this->assertEquals('users.web_edit', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::edit/25', $route->run());
		$route = $this->router->match('post', 'http://domain.tld/users/25/delete');
		$this->assertEquals('users.web_delete', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::delete/25', $route->run());
		$route = $this->router->match('post', 'http://domain.tld/users/25/update');
		$this->assertEquals('users.web_update', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::update/25', $route->run());
	}

	public function testWebResourceWithExcept()
	{
		$this->router->serve('http://domain.tld', function (Collection $collection) {
			$collection->webResource(
				'users',
				'Tests\Routing\Support\Users',
				'users',
				['create', 'show', 'replace', 'web_edit', 'web_update']
			);
		});
		$this->assertResourceWithExcept();
		$route = $this->router->match('get', 'http://domain.tld/users/new');
		$this->assertEquals('users.web_new', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::new', $route->run());
		$route = $this->router->match('get', 'http://domain.tld/users/25/edit');
		$this->assertEquals('not-found', $route->getName());
		$this->assertNull($route->run());
		$route = $this->router->match('post', 'http://domain.tld/users/25/delete');
		$this->assertEquals('users.web_delete', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::delete/25', $route->run());
		$route = $this->router->match('post', 'http://domain.tld/users/25/update');
		$this->assertEquals('not-found', $route->getName());
		$this->assertNull($route->run());
	}

	protected function assertPresenter()
	{
		$route = $this->router->match('get', 'http://domain.tld/admin/users');
		$this->assertEquals('admin.users.index', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::index', $route->run());
		$route = $this->router->match('post', 'http://domain.tld/admin/users');
		$this->assertEquals('admin.users.create', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::create', $route->run());
	}

	public function testPresenter()
	{
		$this->router->serve('http://domain.tld', function (Collection $collection) {
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
	public function testRedirect()
	{
		$this->router->serve('http://domain.tld', function (Collection $collection) {
			$collection->redirect('shop', 'https://shop.com', 301);
		});
		$this->router->match('get', 'http://domain.tld/shop')->run();
		$this->assertContains('Location: https://shop.com', xdebug_get_headers());
		$this->assertEquals(301, \http_response_code());
	}

	public function testRoutes()
	{
		$this->prepare();
		foreach ($this->router->getRoutes() as $routes) {
			foreach ($routes as $route) {
				$this->assertInstanceOf(Route::class, $route);
			}
		}
	}

	public function testCollectionNamespace()
	{
		$this->router->serve('http://foo.com', function (Collection $collection) {
			$collection->namespace('App', [
				$collection->get('/', 'Home::index', 'home'),
				$collection->namespace('\Blog\Test\\', [
					$collection->group('/blog', [
						$collection->get('', 'Blog', 'blog'),
						$collection->get('{num}', 'Posts::show/0', 'post'),
						$collection->get('foo', function () {
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

	public function testCollectionMagicMethods()
	{
		$this->router->serve('http://foo.com', function (Collection $collection) {
			$this->assertEquals('http://foo.com', $collection->origin);
			$this->assertEquals($this->router, $collection->router);
			$this->assertIsArray($collection->routes);
			$this->assertNull($collection->getRouteNotFound());
			$collection->notFound('NotFound::index');
		});
		$this->router->match('get', 'http://foo.com/bla');
		$this->assertEquals('NotFound::index', $this->router->getMatchedRoute()->getAction());
	}

	public function testCollectionMagicMethodNotAllowed()
	{
		$this->expectException(\BadMethodCallException::class);
		$this->expectExceptionMessage(
			'Method not allowed: setOrigin'
		);
		$this->router->serve('http://foo.com', function (Collection $collection) {
			$collection->setOrigin('foo');
		});
	}

	public function testCollectionMagicMethodNotFound()
	{
		$this->expectException(\BadMethodCallException::class);
		$this->expectExceptionMessage(
			'Method not found: foo'
		);
		$this->router->serve('http://foo.com', function (Collection $collection) {
			$collection->foo();
		});
	}

	public function testCollectionMagicPropertyNotAllowed()
	{
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('Property not allowed: namespace');
		$this->router->serve('http://foo.com', function (Collection $collection) {
			$collection->namespace;
		});
	}

	public function testCollectionMagicPropertyNotFound()
	{
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('Property not found: foo');
		$this->router->serve('http://foo.com', function (Collection $collection) {
			$collection->foo;
		});
	}

	public function testBeforeAndAfterRouteActions()
	{
		$this->router->serve('http://foo.com', function (Collection $collection) {
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
