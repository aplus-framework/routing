<?php namespace Tests\Routing;

use Framework\HTTP\Response;
use Framework\Routing\Collection;

/**
 * Class RouterResourceWithExceptTest.
 *
 * @runTestsInSeparateProcesses
 */
class RouterResourceWithExceptTest extends RouterResourceTest
{
	protected function setUp() : void
	{
		$this->collection = static function (Collection $collection) {
			$collection->resource(
				'users',
				'Tests\Routing\Support\Users',
				'users',
				['create', 'show', 'replace']
			);
		};
	}

	public function testCreate() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'POST',
		]);
		$route = $this->router->match();
		$this->assertEquals('not-found', $route->getName());
		$this->assertInstanceOf(Response::class, $route->run());
	}

	public function testShow() : void
	{
		$this->prepare([
			'REQUEST_URI' => '/users/25',
		]);
		$route = $this->router->match();
		$this->assertEquals('not-found', $route->getName());
		$this->assertInstanceOf(Response::class, $route->run());
	}

	public function testReplace() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'PUT',
			'REQUEST_URI' => '/users/25',
		]);
		$route = $this->router->match();
		$this->assertEquals('not-found', $route->getName());
		$this->assertInstanceOf(Response::class, $route->run());
	}
}
