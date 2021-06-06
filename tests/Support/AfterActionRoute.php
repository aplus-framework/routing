<?php namespace Tests\Routing\Support;

use Framework\Routing\RouteAction;

class AfterActionRoute extends RouteAction
{
	public function index()
	{
	}

	protected function afterAction(mixed $response)
	{
		return __METHOD__;
	}
}
