<?php namespace Tests\Routing;

use Framework\Routing\RouteAction;
use PHPUnit\Framework\TestCase;

class RouteActionTest extends TestCase
{
	/**
	 * @var RouteActionMock
	 */
	protected $routeAction;

	protected function setUp() : void
	{
		$this->routeAction = new RouteActionMock();
	}

	public function testAction()
	{
		$this->assertEquals([25], $this->routeAction->show(25));
	}

	public function testActionNotAllowed()
	{
		$this->expectException(\BadMethodCallException::class);
		$this->expectExceptionMessage('Action method not allowed: foo');
		$this->routeAction->foo();
	}

	public function testActionNotFound()
	{
		$this->expectException(\BadMethodCallException::class);
		$this->expectExceptionMessage('Action method not found: bar');
		$this->routeAction->bar();
	}

	public function testMagicActions()
	{
		$this->assertEquals(['method', []], $this->routeAction->beforeAction('method', []));
		$this->assertEquals(['method', []], $this->routeAction->afterAction('method', []));
	}

	public function testNullMagicActions()
	{
		$action = new class() extends RouteAction {
		};
		$this->assertNull($action->beforeAction('method', []));
		$this->assertNull($action->afterAction('method', []));
	}
}
