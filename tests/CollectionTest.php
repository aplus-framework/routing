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

use Framework\Routing\Collection;
use Framework\Routing\Router;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
	protected Collection $collection;

	protected function setUp() : void
	{
		$this->collection = new Collection(new Router(), 'localhost');
	}

	public function testCount() : void
	{
		$this->assertCount(0, $this->collection);
		$this->collection->get('/foo', 'Foo');
		$this->assertCount(1, $this->collection);
	}
}
