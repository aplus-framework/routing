<?php namespace Framework\Routing;

interface FilterInterface
{
	public function before();

	public function after();
}
