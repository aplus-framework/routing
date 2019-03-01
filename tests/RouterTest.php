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

	public function testServe()
	{
		$this->prepare();
		$route = $this->router->match('GET', 'https://domain.tld:8080/users/25');
		self::assertInstanceOf(Route::class, $route);
		self::assertEquals('/users/{num}', $route->getPath());
		self::assertEquals([25], $route->getParams());
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
