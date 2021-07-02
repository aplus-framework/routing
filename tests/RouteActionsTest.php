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
use Tests\Routing\Support\WithRouteActions;

class RouteActionsTest extends TestCase
{
	protected RouteActions $actions;

	protected function setUp() : void
	{
		$this->actions = new WithRouteActions();
	}

	public function testBeforeAction() : void
	{
		self::assertNull($this->actions->beforeAction());
	}

	public function testAfterAction() : void
	{
		self::assertSame('foo', $this->actions->afterAction('foo'));
	}

	public function testActionMethodNotAllowed() : void
	{
		$this->expectException(\BadMethodCallException::class);
		$this->expectExceptionMessage(
			'Action method not allowed: ' . WithRouteActions::class . '::notAllowed'
		);
		$this->actions->notAllowed();
	}

	public function testActionMethodNotFound() : void
	{
		$this->expectException(\BadMethodCallException::class);
		$this->expectExceptionMessage(
			'Action method not found: ' . WithRouteActions::class . '::bazz'
		);
		$this->actions->bazz();
	}

	public function testSetProperties() : void
	{
		$this->actions->actionMethod = 'index';
		$this->actions->actionParams = [];
		$this->actions->actionRun = true;
		$this->expectException(\Error::class);
		$this->expectExceptionMessage(
			'Cannot access property ' . WithRouteActions::class . '::$foo'
		);
		$this->actions->foo = 'bar';
	}
}
