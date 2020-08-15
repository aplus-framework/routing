<?php namespace Tests\Routing;

use Framework\Routing\Exception;
use Framework\Routing\Route;
use Framework\Routing\Router;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
	protected Router $router;
	protected Route $route;

	protected function setUp() : void
	{
		$this->router = new Router();
		$this->route = new Route(
			$this->router,
			'http://domain.tld',
			'/',
			function () {
				echo 'Hello!';
			}
		);
	}

	public function testInit()
	{
		$this->route = new Route(
			$this->router,
			'http://domain.tld',
			'/',
			\Tests\Routing\Support\StopInit::class
		);
		$this->assertEquals('value', $this->route->run());
	}

	public function testClosureFilterNotFound()
	{
		$this->route->setFilters([\Foo::class]);
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Filter class not found: Foo');
		$this->route->run();
	}

	public function testFilterNotFound()
	{
		$this->route = new Route(
			$this->router,
			'http://domain.tld',
			'/',
			\Tests\Routing\Support\Shop::class
		);
		$this->route->setFilters([\Foo::class]);
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Filter class not found: Foo');
		$this->route->run();
	}

	public function testOptionsFilters()
	{
		$this->route->setOptions([
			'filters' => ['Foo', 'Bar'],
		]);
		$this->assertEquals(['Foo', 'Bar'], $this->route->getFilters());
	}
}
