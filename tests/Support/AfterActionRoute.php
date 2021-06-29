<?php
/*
 * This file is part of The Framework Routing Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Routing\Support;

use Framework\Routing\RouteAction;

class AfterActionRoute extends RouteAction
{
	public function index() : void
	{
	}

	protected function afterAction(mixed $response)
	{
		return __METHOD__;
	}
}
