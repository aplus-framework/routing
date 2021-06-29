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

class RouteActionMock extends RouteAction
{
	public function show($id)
	{
		return \func_get_args();
	}

	protected function foo() : void
	{
	}

	protected function beforeAction()
	{
		return 'before';
	}

	protected function afterAction(mixed $response)
	{
		return $response;
	}
}
