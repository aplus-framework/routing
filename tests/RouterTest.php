<?php namespace Tests\Routing;

use Framework\Routing\Collection;
use Framework\Routing\Exception;
use Framework\Routing\Route;
use Framework\Routing\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
	/**
	 * @var \Framework\Routing\Router
	 */
	protected $router;

	public function setup()
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
				'Tests\Routing\Support\Shop::showProduct/1/0/2'
			);
		});
	}

	public function testDefaultRouteNotFound()
	{
		self::assertEquals('not-found', $this->router->match('GET', 'http://site.org')->getName());
	}

	public function testRouteRunWithClass()
	{
		$this->prepare();
		self::assertEquals(
			'Tests\Routing\Support\Shop::index',
			$this->router->match('GET', 'https://domain.tld:8081/shop')->run()
		);
		self::assertEquals(
			'Tests\Routing\Support\Shop::listProducts',
			$this->router->match('GET', 'https://domain.tld:8081/shop/products')->run()
		);
		self::assertEquals(
			[22, 'foo-bar', 'en'],
			$this->router->match('GET', 'https://domain.tld:8081/shop/products/foo-bar/22/en')
				->run()
		);
	}

	public function testRouteRunWithClassNotExists()
	{
		$this->prepare();
		self::expectException(Exception::class);
		self::expectExceptionMessage('Class not exists: Foo');
		$this->router->match('GET', 'https://domain.tld:8081/foo')->run();
	}

	public function testRouteRunWithClassMethodNotExists()
	{
		$this->prepare();
		self::expectException(Exception::class);
		self::expectExceptionMessage('Class method not exists: Tests\Routing\Support\Shop::bar');
		$this->router->match('GET', 'https://domain.tld:8081/bar')->run();
	}

	public function testRouteRunWithUndefinedActionParam()
	{
		$this->prepare();
		$route = $this->router->match('GET', 'https://domain.tld:8081/shop/products/foo-bar/22/br');
		self::expectException(\InvalidArgumentException::class);
		self::expectExceptionMessage('Undefined action param: 2');
		$route->setActionParams([22, 'foo-bar']);
		$route->run();
	}

	public function testRoutePath()
	{
		$this->prepare();
		self::assertEquals(
			'/users/10/posts/20',
			$this->router->getNamedRoute('user.post')->getPath(10, 20)
		);
		self::expectException(\InvalidArgumentException::class);
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
		self::assertEquals('/animals', $this->router->getNamedRoute('animals')->getPath());
		self::assertEquals(
			['x' => 'foo', 'y' => 'bar'],
			$this->router->getNamedRoute('animals')->getOptions()
		);
		self::assertEquals('/animals/cat', $this->router->getNamedRoute('animals.cat')->getPath());
		self::assertEquals(
			['x' => 'xis'],
			$this->router->getNamedRoute('animals.cat')->getOptions()
		);
		self::assertEquals('/animals/dog', $this->router->getNamedRoute('animals.dog')->getPath());
		self::assertEquals(
			['x' => 'xis', 'y' => 'set'],
			$this->router->getNamedRoute('animals.dog')->getOptions()
		);
		self::assertEquals('/users', $this->router->getNamedRoute('users')->getPath());
		self::assertEquals(
			['x' => [0, 'b', ['c']]],
			$this->router->getNamedRoute('users')->getOptions()
		);
		self::assertEquals('/users', $this->router->getNamedRoute('users.create')->getPath());
		self::assertEquals('/users/25', $this->router->getNamedRoute('users.show')->getPath(25));
		self::assertEquals('/users/{num}/panel', $this->router->getNamedRoute('panel')->getPath());
		self::assertEquals(
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
		self::assertEquals(
			'Home::get',
			$this->router->match('GET', $base_url)->getAction()
		);
		self::assertEquals(
			'Home::post',
			$this->router->match('POST', $base_url)->getAction()
		);
		self::assertEquals(
			'Home::put',
			$this->router->match('PUT', $base_url)->getAction()
		);
		self::assertEquals(
			'Home::patch',
			$this->router->match('PATCH', $base_url)->getAction()
		);
		self::assertEquals(
			'Home::delete',
			$this->router->match('DELETE', $base_url)->getAction()
		);
	}

	public function testServe()
	{
		$this->prepare();
		$route = $this->router->match('GET', 'https://domain.tld:8080/users/25');
		self::assertInstanceOf(Route::class, $route);
		self::assertEquals('/users/{num}', $route->getPath());
		self::assertEquals([25], $route->getActionParams());
		self::assertEquals('User page: 25', $route->run());
		$route = $this->router->match('GET', 'https://domain.tld:8080/users/10/posts/15');
		self::assertInstanceOf(Route::class, $route);
		self::assertEquals('/users/{num}/posts/{num}', $route->getPath());
		self::assertEquals('/users/7/posts/8', $route->getPath(7, 8));
		self::assertEquals([10, 15], $route->getActionParams());
		self::assertEquals('User 10, post: 15', $route->run());
	}

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
		self::assertEquals('/contact', $this->router->getNamedRoute('ctt')->getPath());
		self::assertEquals('/', $this->router->getNamedRoute('home')->getPath());
		self::assertNull($this->router->getNamedRoute('unknown'));
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
		$placeholders = '{alpha}/{alphanum}/{any}/{unknown}/{num}/{segment}';
		$patterns = '([a-zA-Z]+)/([a-zA-Z0-9]+)/(.*)/{unknown}/([0-9]+)/([^/]+)';
		$merged = '([a-zA-Z]+)/{alphanum}/(.*)/{unknown}/([0-9]+)/([^/]+)';
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
			'([a-zA-Z]+)/([a-zA-Z0-9]+)/(.*)/([1-5])/([0-9]+)/([^/]+)',
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
		self::assertEquals(
			'any',
			$this->router->match('GET', 'http://example.com')->getAction()
		);
		self::assertEquals(
			'none',
			$this->router->match('GET', 'https://domain.tld')->getAction()
		);
		self::assertEquals(
			'scheme-subdomain-port',
			$this->router->match('GET', 'http://test.domain.tld:8081')->getAction()
		);
		self::assertEquals(
			'scheme',
			$this->router->match('GET', 'https://subdomain.domain.tld:8080')->getAction()
		);
		self::assertEquals(
			'port',
			$this->router->match('GET', 'http://subdomain.domain.tld:8081')->getAction()
		);
		self::assertEquals(
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
		self::assertEquals('auto-options', $route->getName());
		$route->run();
		self::assertContains(
			'Allow: DELETE, GET, HEAD, OPTIONS, PATCH, PUT',
			\xdebug_get_headers()
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
		self::assertEquals('not-found', $route->getName());
		$route->run();
		self::assertNotContains(
			'Allow: DELETE, GET, HEAD, OPTIONS, PATCH, PUT',
			\xdebug_get_headers()
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
		self::assertNull($route->getName());
		$route->run();
		self::assertNotContains(
			'Allow: DELETE, GET, HEAD, OPTIONS, PATCH, PUT',
			\xdebug_get_headers()
		);
		self::assertContains(
			'Foo: bar',
			\xdebug_get_headers()
		);
	}

	protected function assertResource()
	{
		$route = $this->router->match('get', 'http://domain.tld/users');
		self::assertEquals('users.index', $route->getName());
		self::assertEquals('Tests\Routing\Support\Users::index', $route->run());
		$route = $this->router->match('post', 'http://domain.tld/users');
		self::assertEquals('users.create', $route->getName());
		self::assertEquals('Tests\Routing\Support\Users::create', $route->run());
		$route = $this->router->match('get', 'http://domain.tld/users/25');
		self::assertEquals('users.show', $route->getName());
		self::assertEquals('Tests\Routing\Support\Users::show/25', $route->run());
		$route = $this->router->match('patch', 'http://domain.tld/users/25');
		self::assertEquals('users.update', $route->getName());
		self::assertEquals('Tests\Routing\Support\Users::update/25', $route->run());
		$route = $this->router->match('put', 'http://domain.tld/users/25');
		self::assertEquals('users.replace', $route->getName());
		self::assertEquals('Tests\Routing\Support\Users::replace/25', $route->run());
		$route = $this->router->match('delete', 'http://domain.tld/users/25');
		self::assertEquals('users.delete', $route->getName());
		self::assertEquals('Tests\Routing\Support\Users::delete/25', $route->run());
	}

	protected function assertResourceWithExcept()
	{
		$route = $this->router->match('get', 'http://domain.tld/users');
		self::assertEquals('users.index', $route->getName());
		self::assertEquals('Tests\Routing\Support\Users::index', $route->run());
		$route = $this->router->match('post', 'http://domain.tld/users');
		self::assertEquals('not-found', $route->getName());
		self::assertNull($route->run());
		$route = $this->router->match('get', 'http://domain.tld/users/25');
		self::assertEquals('not-found', $route->getName());
		self::assertNull($route->run());
		$route = $this->router->match('patch', 'http://domain.tld/users/25');
		self::assertEquals('users.update', $route->getName());
		self::assertEquals('Tests\Routing\Support\Users::update/25', $route->run());
		$route = $this->router->match('put', 'http://domain.tld/users/25');
		self::assertEquals('not-found', $route->getName());
		self::assertNull($route->run());
		$route = $this->router->match('delete', 'http://domain.tld/users/25');
		self::assertEquals('users.delete', $route->getName());
		self::assertEquals('Tests\Routing\Support\Users::delete/25', $route->run());
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
		self::assertEquals('users.web_new', $route->getName());
		self::assertEquals('Tests\Routing\Support\Users::new', $route->run());
		$route = $this->router->match('get', 'http://domain.tld/users/25/edit');
		self::assertEquals('users.web_edit', $route->getName());
		self::assertEquals('Tests\Routing\Support\Users::edit/25', $route->run());
		$route = $this->router->match('post', 'http://domain.tld/users/25/delete');
		self::assertEquals('users.web_delete', $route->getName());
		self::assertEquals('Tests\Routing\Support\Users::delete/25', $route->run());
		$route = $this->router->match('post', 'http://domain.tld/users/25/update');
		self::assertEquals('users.web_update', $route->getName());
		self::assertEquals('Tests\Routing\Support\Users::update/25', $route->run());
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
		self::assertEquals('users.web_new', $route->getName());
		self::assertEquals('Tests\Routing\Support\Users::new', $route->run());
		$route = $this->router->match('get', 'http://domain.tld/users/25/edit');
		self::assertEquals('not-found', $route->getName());
		self::assertNull($route->run());
		$route = $this->router->match('post', 'http://domain.tld/users/25/delete');
		self::assertEquals('users.web_delete', $route->getName());
		self::assertEquals('Tests\Routing\Support\Users::delete/25', $route->run());
		$route = $this->router->match('post', 'http://domain.tld/users/25/update');
		self::assertEquals('not-found', $route->getName());
		self::assertNull($route->run());
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
		self::assertContains('Location: https://shop.com', \xdebug_get_headers());
		self::assertEquals(301, \http_response_code());
	}

	public function testRoutes()
	{
		$this->prepare();
		foreach ($this->router->getRoutes() as $routes) {
			foreach ($routes as $route) {
				self::assertInstanceOf(Route::class, $route);
			}
		}
	}
}
