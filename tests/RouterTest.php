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
			$collection->get('/users/{num}', function () {
				return 'User page';
			});
		});
	}

	public function testServe()
	{
		$this->prepare();
		$route = $this->router->match('GET', 'https://domain.tld:8080/users/25');
		self::assertInstanceOf(Route::class, $route);
		self::assertEquals('/users/{num}', $route->getPath());
		self::assertEquals([25], $route->getParams());
		self::assertEquals('User page', $route->run());
	}
}
