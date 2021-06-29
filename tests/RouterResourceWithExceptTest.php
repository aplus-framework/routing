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
		$this->collection = static function (Collection $collection) : void {
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
