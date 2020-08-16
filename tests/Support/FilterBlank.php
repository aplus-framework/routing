<?php namespace Tests\Routing\Support;

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Routing\Filter;

class FilterBlank extends Filter
{
	public function before(Request $request, Response $response) : void
	{
	}

	public function after(Request $request, Response $response) : void
	{
	}
}
