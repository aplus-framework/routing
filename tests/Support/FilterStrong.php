<?php namespace Tests\Routing\Support;

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Routing\Filter;

class FilterStrong extends Filter
{
	public function after(Request $request, Response $response) : void
	{
		$response->setBody(
			'<b>' . $response->getBody() . '</b>'
		);
	}
}
