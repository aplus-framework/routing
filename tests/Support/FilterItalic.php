<?php namespace Tests\Routing\Support;

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Routing\Filter;

class FilterItalic extends Filter
{
	public function before(Request $request, Response $response)
	{
		$response->setBody(
			'<i>Before</i>'
		);
	}
}
