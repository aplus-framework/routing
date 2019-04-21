<?php namespace Tests\Routing\Support;

use Framework\Routing\RouteAction;

class BeforeActionRoute extends RouteAction
{
	protected function beforeAction(string $action, array $params = [])
	{
		return __METHOD__;
	}

	public function index()
	{
		return __METHOD__;
	}
}
