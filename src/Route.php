<?php namespace Framework\Routing;

class Route
{
	protected $collection;
	protected $path;
	protected $function;
	protected $params = [];

	public function __construct(Collection $collection, string $path, $function)
	{
		$this->collection = $collection;
		$this->setPath($path);
		$this->function = $function;
	}

	protected function setPath(string $path)
	{
		$this->path = '/' . \trim($path, '/');
	}

	public function getPath() : string
	{
		return $this->path;
	}

	public function getFunction()
	{
		return $this->function;
	}

	public function getParams() : array
	{
		return $this->params;
	}

	public function setParams(array $params)
	{
		$this->params = $params;
		return $this;
	}

	public function run(...$construct)
	{
		$function = $this->getFunction();
		if ( ! \is_string($function)) {
			return $function($construct);
		}
		[$class, $method] = \explode('::', $function);
		$class = new $class(...$construct);
		return $class->{$method}(...$this->getParams());
	}
}
