<?php namespace Tests\Routing;

use Framework\Routing\Collection;
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
			$collection->get('contact', function () {
				return 'Contact page';
			}, 'ctt');
			$collection->get('', function () {
				return 'Home page';
			})->setName('home');
		});
	}

	public function testGroup()
	{
		$this->router->serve('{scheme}://domain.tld:{num}', function (Collection $collection) {
			$collection->group('animals', [
				$collection->get('', 'Animals::index', 'animals'),
				$collection->get('cat', 'Animals::cat', 'animals.cat'),
				$collection->get('dog', 'Animals::dog', 'animals.dog'),
			]);
			$collection->group('users', [
				$collection->get('', 'Users::index', 'users'),
				$collection->post('', 'Users::index', 'users.create'),
				$collection->get('{num}', 'Users::show/0', 'users.show'),
				$collection->group('{num}/panel', [
					$collection->get('', 'Panel::index', 'panel'),
					$collection->group('config', [
						$collection->get('update', 'Panel::config', 'panel.update'),
					]),
				]),
			]);
		});
		self::assertEquals('/animals', $this->router->getNamedRoute('animals')->getPath());
		self::assertEquals('/animals/cat', $this->router->getNamedRoute('animals.cat')->getPath());
		self::assertEquals('/animals/dog', $this->router->getNamedRoute('animals.dog')->getPath());
		self::assertEquals('/users', $this->router->getNamedRoute('users')->getPath());
		self::assertEquals('/users', $this->router->getNamedRoute('users.create')->getPath());
		self::assertEquals('/users/{num}', $this->router->getNamedRoute('users.show')->getPath());
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
			$this->router->match('GET', $base_url)->getFunction()
		);
		self::assertEquals(
			'Home::post',
			$this->router->match('POST', $base_url)->getFunction()
		);
		self::assertEquals(
			'Home::put',
			$this->router->match('PUT', $base_url)->getFunction()
		);
		self::assertEquals(
			'Home::patch',
			$this->router->match('PATCH', $base_url)->getFunction()
		);
		self::assertEquals(
			'Home::delete',
			$this->router->match('DELETE', $base_url)->getFunction()
		);
	}

	public function testServe()
	{
		$this->prepare();
		$route = $this->router->match('GET', 'https://domain.tld:8080/users/25');
		self::assertInstanceOf(Route::class, $route);
		self::assertEquals('/users/{num}', $route->getPath());
		self::assertEquals([25], $route->getFunctionParams());
		self::assertEquals('User page: 25', $route->run());
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
}
