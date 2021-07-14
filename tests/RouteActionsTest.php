<?php
/*
 * This file is part of Aplus Framework Routing Library.
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

/**
 * Class RouteActionsTest.
 */
final class RouteActionsTest extends TestCase
{
    protected RouteActions $actions;

    protected function setUp() : void
    {
        $this->actions = new WithRouteActions();
    }

    public function testBeforeAction() : void
    {
        self::assertNull($this->actions->beforeAction('method', [])); // @phpstan-ignore-line
    }

    public function testAfterAction() : void
    {
        // @phpstan-ignore-next-line
        self::assertSame('result', $this->actions->afterAction('method', [], false, 'result'));
    }

    public function testActionMethodNotAllowed() : void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Action method not allowed: ' . WithRouteActions::class . '::notAllowed'
        );
        $this->actions->notAllowed(); // @phpstan-ignore-line
    }

    public function testActionMethodNotFound() : void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Action method not found: ' . WithRouteActions::class . '::bazz'
        );
        $this->actions->bazz(); // @phpstan-ignore-line
    }
}
