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

use Closure;
use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Routing\Collection;
use Framework\Routing\Router;
use PHPUnit\Framework\TestCase;

/**
 * Class RouterResourceTest.
 *
 * @runTestsInSeparateProcesses
 */
class RouterResourceTest extends TestCase
{
	protected Closure $collection;
	protected Response $response;
	protected Router $router;

	protected function setUp() : void
	{
		$this->collection = static function (Collection $collection) : void {
			$collection->resource('users', 'Tests\Routing\Support\Users', 'users');
		};
	}

	/**
	 * @param array<string,float|int|string> $server
	 */
	protected function prepare(array $server = []) : void
	{
		$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['HTTP_HOST'] = 'domain.tld';
		$_SERVER['REQUEST_URI'] = '/users';
		foreach ($server as $key => $value) {
			$_SERVER[$key] = $value;
		}
		$this->response = new Response(new Request());
		$this->router = new Router($this->response);
		$this->router->serve('http://domain.tld', $this->collection);
	}

	public function testIndex() : void
	{
		$this->prepare();
		$route = $this->router->match();
		$this->assertEquals('users.index', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::index', $route->run());
	}

	public function testCreate() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'POST',
		]);
		$route = $this->router->match();
		$this->assertEquals('users.create', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::create', $route->run());
	}

	public function testShow() : void
	{
		$this->prepare([
			'REQUEST_URI' => '/users/25',
		]);
		$route = $this->router->match();
		$this->assertEquals('users.show', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::show/25', $route->run());
	}

	public function testUpdate() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'PATCH',
			'REQUEST_URI' => '/users/25',
		]);
		$route = $this->router->match();
		$this->assertEquals('users.update', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::update/25', $route->run());
	}

	public function testReplace() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'PUT',
			'REQUEST_URI' => '/users/25',
		]);
		$route = $this->router->match();
		$this->assertEquals('users.replace', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::replace/25', $route->run());
	}

	public function testDelete() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'DELETE',
			'REQUEST_URI' => '/users/25',
		]);
		$route = $this->router->match();
		$this->assertEquals('users.delete', $route->getName());
		$this->assertEquals('Tests\Routing\Support\Users::delete/25', $route->run());
	}
}
