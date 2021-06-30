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

use Framework\Routing\RouteActions;
use PHPUnit\Framework\TestCase;

class RouteActionsTest extends TestCase
{
	protected RouteActionsMock $routeActions;

	protected function setUp() : void
	{
		$this->routeActions = new RouteActionsMock();
	}

	public function testAction() : void
	{
		$this->assertEquals([25], $this->routeActions->show(25));
	}

	public function testActionNotAllowed() : void
	{
		$this->expectException(\BadMethodCallException::class);
		$this->expectExceptionMessage('Action method not allowed: foo');
		$this->routeActions->foo();
	}

	public function testActionNotFound() : void
	{
		$this->expectException(\BadMethodCallException::class);
		$this->expectExceptionMessage('Action method not found: bar');
		$this->routeActions->bar();
	}

	public function testPropertyNotAllowed() : void
	{
		$this->expectException(\Error::class);
		$this->expectExceptionMessage('Cannot access property Tests\Routing\RouteActionsMock::$foo');
		$this->routeActions->foo = 'bar';
	}

	public function testMagicActions() : void
	{
		$this->assertEquals('before', $this->routeActions->beforeAction());
		$this->assertEquals('after', $this->routeActions->afterAction('after'));
	}

	public function testNullMagicActions() : void
	{
		$action = new class() extends RouteActions {
		};
		$this->assertNull($action->beforeAction());
		$this->assertNull($action->afterAction(null));
	}
}
