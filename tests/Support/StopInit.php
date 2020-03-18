<?php namespace Tests\Routing\Support;

class StopInit
{
	public function init()
	{
		return 'value';
	}

	public function index()
	{
		echo 'Hello!';
	}
}
