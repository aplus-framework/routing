<?php namespace Framework\Routing;

class Route
{
	protected $router;
	protected $origin;
	protected $path;
	protected $action;
	protected $actionParams = [];
	protected $name;
	protected $options = [];

	/**
	 * Route constructor.
	 *
	 * @param \Framework\Routing\Router $router
	 * @param string                    $origin URL Origin. A string in the following format:
	 *                                          {scheme}://{hostname}[:{port}]
	 * @param string                    $path   URL Path. A string starting with '/'
	 * @param \Closure|string           $action
	 */
	public function __construct(Router $router, string $origin, string $path, $action)
	{
		$this->router = $router;
		$this->setOrigin($origin);
		$this->setPath($path);
		$this->setAction($action);
	}

	public function getOrigin(...$params) : string
	{
		if ($params) {
			return $this->router->fillPlaceholders($this->origin, ...$params);
		}
		return $this->origin;
	}

	protected function setOrigin(string $origin)
	{
		$this->origin = \ltrim($origin, '/');
		return $this;
	}

	public function getURL(array $origin_params = [], array $path_params = []) : string
	{
		return $this->getOrigin(...$origin_params) . $this->getPath(...$path_params);
	}

	public function getOptions() : array
	{
		return $this->options;
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

	public function getAction()
	{
		return $this->action;
	}

	public function setAction($action)
	{
		$this->action = \is_string($action) ? \trim($action, '\\') : $action;
		return $this;
	}

	public function getActionParams() : array
	{
		return $this->actionParams;
	}

	public function setActionParams(array $params)
	{
		$this->actionParams = $params;
		return $this;
	}

	public function run(...$construct)
	{
		$action = $this->getAction();
		if ( ! \is_string($action)) {
			return $action($this->getActionParams(), ...$construct);
		}
		if (\strpos($action, '::') === false) {
			$action .= '::' . $this->router->getDefaultRouteActionMethod();
		}
		[$classname, $action] = \explode('::', $action, 2);
		[$action, $params] = $this->extractActionAndParams($action);
		if ( ! \class_exists($classname)) {
			throw new Exception("Class not exists: {$classname}");
		}
		$class = new $classname(...$construct);
		if ( ! \method_exists($class, $action)) {
			throw new Exception(
				"Class method not exists: {$classname}::{$action}"
			);
		}
		return $class->{$action}(...$params);
	}

	/**
	 * @param string $action An action part like: index/0/2/1
	 *
	 * @return array
	 */
	protected function extractActionAndParams(string $action) : array
	{
		if (\strpos($action, '/') === false) {
			return [$action, []];
		}
		$params = \explode('/', $action);
		$action = $params[0];
		unset($params[0]);
		if ($params) {
			$action_params = $this->getActionParams();
			$params = \array_values($params);
			foreach ($params as $index => $param) {
				if ( ! \array_key_exists($param, $action_params)) {
					throw new \InvalidArgumentException("Undefined action param: {$param}");
				}
				$params[$index] = $action_params[$param];
			}
		}
		return [
			$action,
			$params,
		];
	}
}
