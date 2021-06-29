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
