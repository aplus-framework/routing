<?php namespace Framework\Routing;

class Route
{
	protected $router;
	protected $baseURL;
	protected $path;
	protected $function;
	protected $functionParams = [];
	protected $name;
	protected $options = [];

	public function __construct(Router $router, string $base_url, string $path, $function)
	{
		$this->router = $router;
		$this->setBaseURL($base_url);
		$this->setPath($path);
		$this->function = $function;
	}

	public function getBaseURL(...$params) : string
	{
		if ($params) {
			return $this->router->fillPlaceholders($this->baseURL, ...$params);
		}
		return $this->baseURL;
	}

	protected function setBaseURL(string $base_url)
	{
		$this->baseURL = \ltrim($base_url, '/');
		return $this;
	}

	public function getURL(array $base_url_params = [], array $path_params = []) : string
	{
		return $this->getBaseURL(...$base_url_params) . $this->getPath(...$path_params);
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
		return $this;
	}

	public function getPath(...$params) : string
	{
		if ($params) {
			return $this->router->fillPlaceholders($this->path, ...$params);
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
			$function .= '::' . $this->router->getDefaultRouteFunction();
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
