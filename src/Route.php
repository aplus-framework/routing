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
		if (\strpos($function, '::') === false) {
			$function .= '::' . $this->collection->getRouter()->getDefaultRouteFunction();
		}
		[$classname, $function] = \explode('::', $function, 2);
		[$function, $params] = $this->extractFunctionAndParams($function);
		if ( ! \class_exists($classname)) {
			throw new Exception("Class not exists: {$classname}");
		}
		$class = new $classname(...$construct);
		if ( ! \method_exists($class, $function)) {
			throw new Exception(
				"Class method not exists: {$classname}::{$function}"
			);
		}
		return $class->{$function}(...$params);
	}

	/**
	 * @param string $function A function part like: index/0/2/1
	 *
	 * @return array
	 */
	protected function extractFunctionAndParams(string $function) : array
	{
		if (\strpos($function, '/') === false) {
			return [$function, []];
		}
		$params = \explode('/', $function);
		$function = $params[0];
		unset($params[0]);
		if ($params) {
			$function_params = $this->getFunctionParams();
			$params = \array_values($params);
			foreach ($params as $index => $param) {
				if ( ! \array_key_exists($param, $function_params)) {
					throw new \InvalidArgumentException("Undefined function param: {$param}");
				}
				$params[$index] = $function_params[$param];
			}
		}
		return [
			$function,
			$params,
		];
	}
}
