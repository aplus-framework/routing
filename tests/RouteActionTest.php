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

use Framework\Routing\RouteAction;
use PHPUnit\Framework\TestCase;

class RouteActionTest extends TestCase
{
	protected RouteActionMock $routeAction;

	protected function setUp() : void
	{
		$this->routeAction = new RouteActionMock();
	}

	public function testAction() : void
	{
		$this->assertEquals([25], $this->routeAction->show(25));
	}

	public function testActionNotAllowed() : void
	{
		$this->expectException(\BadMethodCallException::class);
		$this->expectExceptionMessage('Action method not allowed: foo');
		$this->routeAction->foo();
	}

	public function testActionNotFound() : void
	{
		$this->expectException(\BadMethodCallException::class);
		$this->expectExceptionMessage('Action method not found: bar');
		$this->routeAction->bar();
	}

	public function testPropertyNotAllowed() : void
	{
		$this->expectException(\Error::class);
		$this->expectExceptionMessage('Cannot access property Tests\Routing\RouteActionMock::$foo');
		$this->routeAction->foo = 'bar';
	}

	public function testMagicActions() : void
	{
		$this->assertEquals('before', $this->routeAction->beforeAction());
		$this->assertEquals('after', $this->routeAction->afterAction('after'));
	}

	public function testNullMagicActions() : void
	{
		$action = new class() extends RouteAction {
		};
		$this->assertNull($action->beforeAction());
		$this->assertNull($action->afterAction(null));
	}
}
