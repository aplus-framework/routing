<?php namespace Tests\Routing;

use Framework\Routing\Collection;
use Framework\Routing\Router;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
	/**
	 * @var Collection
	 */
	protected $collection;

	protected function setUp() : void
	{
		$this->collection = new Collection(new Router(), 'localhost');
	}

	public function testCount()
	{
		$this->assertEquals(0, \count($this->collection));
		$this->collection->get('/foo', 'Foo');
		$this->assertEquals(1, \count($this->collection));
	}
}
