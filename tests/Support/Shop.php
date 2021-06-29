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

class Shop extends RouteAction
{
	public function index()
	{
		return __METHOD__;
	}

	public function listProducts()
	{
		return __METHOD__;
	}

	public function showProduct(int $id, string $slug, string $lang)
	{
		return [$id, $slug, $lang];
	}
}
