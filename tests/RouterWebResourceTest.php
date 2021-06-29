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

use Framework\Routing\Collection;

/**
 * Class RouterWebResourceTest.
 *
 * @runTestsInSeparateProcesses
 */
class RouterWebResourceTest extends RouterResourceTest
{
	protected function setUp() : void
	{
		$this->collection = static function (Collection $collection) : void {
			$collection->webResource('users', 'Tests\Routing\Support\Users', 'users');
		};
	}

	public function testWebNew() : void
	{
		$this->prepare([
			'REQUEST_URI' => '/users/new',
		]);
		$route = $this->router->match();
		$this->assertEquals('users.web_new', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::new', $route->run());
	}

	public function testWebEdit() : void
	{
		$this->prepare([
			'REQUEST_URI' => '/users/25/edit',
		]);
		$route = $this->router->match();
		$this->assertEquals('users.web_edit', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::edit/25', $route->run());
	}

	public function testWebUpdate() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'POST',
			'REQUEST_URI' => '/users/25/update',
		]);
		$route = $this->router->match();
		$this->assertEquals('users.web_update', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::update/25', $route->run());
	}

	public function testWebDelete() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'POST',
			'REQUEST_URI' => '/users/25/delete',
		]);
		$route = $this->router->match();
		$this->assertEquals('users.web_delete', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::delete/25', $route->run());
	}
}
