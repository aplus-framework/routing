<?php namespace Tests\Routing\Support;

class Users
{
	public function index()
	{
		return __METHOD__;
	}

	public function show($num)
	{
		return __METHOD__ . '/' . $num;
	}

	public function create()
	{
		return __METHOD__;
	}

	public function delete()
	{
		return __METHOD__;
	}

	public function update()
	{
		return __METHOD__;
	}

	public function replace()
	{
		return __METHOD__;
	}
}
