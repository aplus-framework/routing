<?php namespace Tests\Routing\Support;

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Routing\Filter;

class FilterBefore extends Filter
{
	public function before(Request $request, Response $response)
	{
		return 'Foo';
	}
}
