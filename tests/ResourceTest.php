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

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Routing\RouteCollection;
use Framework\Routing\Router;
use PHPUnit\Framework\TestCase;
use Tests\Routing\Support\UsersRouteActionsResource;

/**
 * Class ResourceTest.
 */
class ResourceTest extends TestCase
{
	protected Response $response;
	protected Router $router;

	protected function setUp() : void
	{
		$this->prepare([
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'REQUEST_METHOD' => 'GET',
			'HTTP_HOST' => 'api.domain.tld',
			'REQUEST_URI' => '/users',
		]);
	}

	/**
	 * @param array<string,mixed> $server
	 */
	protected function prepare(array $server = []) : void
	{
		foreach ($server as $key => $value) {
			$_SERVER[$key] = $value;
		}
		$this->response = new Response(new Request());
		$this->router = new Router($this->response);
		$this->router->serve(
			'http://api.domain.tld',
			static function (RouteCollection $routes) : void {
				$routes->resource('/users', UsersRouteActionsResource::class, 'api.users');
			}
		);
	}

	public function testIndex() : void
	{
		self::assertSame(
			UsersRouteActionsResource::class . '::index',
			$this->router->match()->run()->getBody()
		);
		self::assertSame('api.users.index', $this->router->getMatchedRoute()->getName());
	}

	public function testCreate() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'POST',
		]);
		self::assertSame(
			UsersRouteActionsResource::class . '::create',
			$this->router->match()->run()->getBody()
		);
		self::assertSame('api.users.create', $this->router->getMatchedRoute()->getName());
	}

	public function testShow() : void
	{
		$this->prepare([
			'REQUEST_URI' => '/users/6',
		]);
		self::assertSame(
			UsersRouteActionsResource::class . '::show/6',
			$this->router->match()->run()->getBody()
		);
		self::assertSame('api.users.show', $this->router->getMatchedRoute()->getName());
	}

	public function testUpdate() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'PATCH',
			'REQUEST_URI' => '/users/23',
		]);
		self::assertSame(
			UsersRouteActionsResource::class . '::update/23',
			$this->router->match()->run()->getBody()
		);
		self::assertSame('api.users.update', $this->router->getMatchedRoute()->getName());
	}

	public function testReplace() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'PUT',
			'REQUEST_URI' => '/users/42',
		]);
		self::assertSame(
			UsersRouteActionsResource::class . '::replace/42',
			$this->router->match()->run()->getBody()
		);
		self::assertSame('api.users.replace', $this->router->getMatchedRoute()->getName());
	}

	public function testDelete() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'DELETE',
			'REQUEST_URI' => '/users/66',
		]);
		self::assertSame(
			UsersRouteActionsResource::class . '::delete/66',
			$this->router->match()->run()->getBody()
		);
		self::assertSame('api.users.delete', $this->router->getMatchedRoute()->getName());
	}

	public function testOptions() : void
	{
		$this->prepare([
			'REQUEST_METHOD' => 'OPTIONS',
			'REQUEST_URI' => '/users/69',
		]);
		$this->router->setAutoOptions();
		self::assertSame(
			'',
			$this->router->match()->run()->getBody()
		);
		self::assertSame(
			'DELETE, GET, HEAD, OPTIONS, PATCH, PUT',
			$this->response->getHeader('Allow')
		);
		self::assertSame('auto-allow-200', $this->router->getMatchedRoute()->getName());
	}
}
