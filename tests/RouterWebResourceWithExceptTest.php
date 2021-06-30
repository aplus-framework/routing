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
use Framework\Routing\RouteCollection;

/**
 * Class RouterWebResourceWithExceptTest.
 *
 * @runTestsInSeparateProcesses
 */
class RouterWebResourceWithExceptTest extends RouterWebResourceTest
{
	protected function setUp() : void
	{
		$this->collection = static function (RouteCollection $collection) : void {
			$collection->webResource(
				'users',
				'Tests\Routing\Support\Users',
				'users',
				['replace', 'web_edit']
			);
		};
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

	public function testWebEdit() : void
	{
		$this->prepare([
			'REQUEST_URI' => '/users/25/edit',
		]);
		$route = $this->router->match();
		$this->assertEquals('not-found', $route->getName());
		$this->assertInstanceOf(Response::class, $route->run());
	}
}
