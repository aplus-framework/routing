<?php namespace Tests\Routing\Support;

class Users
{
	public function index()
	{
		return __METHOD__;
	}

	public function new()
	{
		return __METHOD__;
	}

	public function create()
	{
		return __METHOD__;
	}

	public function show($num)
	{
		return __METHOD__ . '/' . $num;
	}

	public function edit($num)
	{
		return __METHOD__ . '/' . $num;
	}

	public function delete($num)
	{
		return __METHOD__ . '/' . $num;
	}

	public function update($num)
	{
		return __METHOD__ . '/' . $num;
	}

	public function replace($num)
	{
		return __METHOD__ . '/' . $num;
	}
}
