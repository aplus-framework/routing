<?php namespace Tests\Routing\Support;

use Framework\Routing\RouteAction;

class AfterActionRoute extends RouteAction
{
	public function index()
	{
	}

	protected function afterAction(string $action, array $params = [])
	{
		return __METHOD__;
	}
}
