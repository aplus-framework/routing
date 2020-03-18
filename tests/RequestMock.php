<?php namespace Tests\Routing;

use Framework\HTTP\Request;

class RequestMock extends Request
{
	protected function setProtocol(?string $protocol)
	{
		$protocol = 'HTTP/1.1';
		return parent::setProtocol($protocol);
	}

	protected function setMethod(?string $method)
	{
		$method = 'GET';
		return parent::setMethod($method);
	}

	protected function setURL($url)
	{
		$url = 'http://localhost';
		return parent::setURL($url);
	}
}
