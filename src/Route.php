<?php namespace Framework\Routing;

class Route
{
	protected $collection;
	protected $path;
	protected $function;
	protected $functionParams = [];
	protected $name;
	protected $options = [];

	public function __construct(Collection $collection, string $path, $function)
	{
		$this->collection = $collection;
		$this->setPath($path);
		$this->function = $function;
	}

	public function getOptions() : array
	{
		return $this->options;
	}

	public function addOptions(array $options)
	{
		$this->options = \array_replace_recursive($this->options, $options);
		return $this;
	}

	public function setOptions(array $options)
	{
		$this->options = $options;
		return $this;
	}

	public function getName() : ?string
	{
		return $this->name;
	}

	public function setName(string $name)
	{
		$this->name = $name;
		return $this;
	}

	public function setPath(string $path)
	{
		$this->path = '/' . \trim($path, '/');
	}

	public function getPath(...$params) : string
	{
		if ($params) {
			return $this->collection->getRouter()
				->fillPlaceholders($this->path, ...$params);
		}
		return $this->path;
	}

	public function getFunction()
	{
		return $this->function;
	}

	public function getFunctionParams() : array
	{
		return $this->functionParams;
	}

	public function setFunctionParams(array $params)
	{
		$this->functionParams = $params;
		return $this;
	}

	public function run(...$construct)
	{
		$function = $this->getFunction();
		if ( ! \is_string($function)) {
			return $function($this->getFunctionParams(), ...$construct);
		}
		[$class, $method] = \explode('::', $function);
		$class = new $class(...$construct);
		return $class->{$method}(...$this->getFunctionParams());
	}
}
