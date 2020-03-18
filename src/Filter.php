<?php namespace Framework\Routing;

use Framework\HTTP\Request;
use Framework\HTTP\Response;

abstract class Filter
{
	/**
	 * Runs before the Route Action. Useful to intercept requests.
	 * Returning any value will cause the route action to not be performed.
	 *
	 * @param Request  $request
	 * @param Response $response
	 *
	 * @return mixed
	 */
	public function before(Request $request, Response $response)
	{
	}

	/**
	 * Runs after the Route Action. Useful to modify the response.
	 * Returning any value will NOT stop other filters.
	 *
	 * @param Request  $request
	 * @param Response $response
	 */
	public function after(Request $request, Response $response) : void
	{
	}
}
