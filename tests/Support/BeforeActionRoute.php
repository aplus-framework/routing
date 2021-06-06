<?php namespace Tests\Routing\Support;

use Framework\Routing\RouteAction;

class BeforeActionRoute extends RouteAction
{
	protected function beforeAction()
	{
		return __METHOD__;
	}

	public function index()
	{
		return __METHOD__;
	}
}
