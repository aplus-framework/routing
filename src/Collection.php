<?php namespace Framework\Routing;

class Collection
{
	/**
	 * @var \Framework\Routing\Router
	 */
	protected $router;
	/**
	 * @var string
	 */
	protected $origin;
	/**
	 * Array of HTTP Methods as keys and array of Routes as values.
	 *
	 * @var array
	 */
	protected $routes = [];
	/**
	 * @var Route|null
	 */
	protected $notFound;
	/**
	 * @var string|null
	 */
	protected $namespace;

	public function __construct(Router $router, string $origin)
	{
		$this->router = $router;
		$this->setOrigin($origin);
	}

	public function __call($name, $arguments)
	{
		switch ($name) {
			case 'getRouteNotFound':
				return $this->getRouteNotFound();
				break;
		}
	}

	public function __get($name)
	{
		switch ($name) {
			case 'origin':
				return $this->origin;
				break;
			case 'router':
				return $this->router;
				break;
			case 'routes':
				return $this->routes;
				break;
		}
	}

	protected function setOrigin(string $origin)
	{
		$this->origin = \ltrim($origin, '/');
		return $this;
	}

	protected function addRoute(string $http_method, Route $route)
	{
		$this->routes[\strtoupper($http_method)][] = $route;
		return $this;
	}

	/*public function namespace(string $namespace, callable $callable) : void
	{
		$this->namespace = \trim($namespace, '\\');
		$callable($this);
		$this->namespace = null;
	}*/
	/**
	 * Gets the Collection Base URL.
	 *
	 * @param mixed ...$params Parameters to fill the Base URL placeholders
	 * @param mixed $action
	 *
	 * @return string
	 */
	/*private function origin(...$params) : string
	{
		if ($params) {
			return $this->router->fillPlaceholders($this->origin, ...$params);
		}
		return $this->origin;
	}*/

	/**
	 * Sets the action to the Collection Route Not Found.
	 *
	 * @param callable|string $action the Route function to run when no Route path is found for
	 *                                this collection
	 */
	public function notFound($action) : void
	{
		$this->notFound = $action;
	}

	/**
	 * Gets the Route Not Found for this Collection.
	 *
	 * @see notFound()
	 *
	 * @return \Framework\Routing\Route|null the Route containing the Not Found Action or null if
	 *                                       the Action was not set
	 */
	protected function getRouteNotFound() : ?Route
	{
		return empty($this->notFound)
			? null
			: new Route(
				$this->router,
				$this->router->getMatchedOrigin(),
				$this->router->getMatchedPath(),
				$this->notFound
			);
	}

	public function add(array $http_methods, string $path, $action, string $name = null) : Route
	{
		/*if (\is_string($action)) {
			$action = $this->namespace . '\\' . \rtrim($action, '\\');
		}*/
		$route = new Route($this->router, $this->origin, $path, $action);
		if ($name) {
			$route->setName($name);
		}
		foreach ($http_methods as $method) {
			$this->addRoute($method, $route);
		}
		return $route;
	}

	public function get(string $path, $action, string $name = null) : Route
	{
		return $this->add(['GET'], $path, $action, $name);
	}

	public function post(string $path, $action, string $name = null) : Route
	{
		return $this->add(['POST'], $path, $action, $name);
	}

	public function put(string $path, $action, string $name = null) : Route
	{
		return $this->add(['PUT'], $path, $action, $name);
	}

	public function patch(string $path, $action, string $name = null) : Route
	{
		return $this->add(['PATCH'], $path, $action, $name);
	}

	public function delete(string $path, $action, string $name = null) : Route
	{
		return $this->add(['DELETE'], $path, $action, $name);
	}

	public function options(string $path, $action, string $name = null) : Route
	{
		return $this->add(['OPTIONS'], $path, $action, $name);
	}

	public function redirect(string $path, string $url, int $code = 302) : Route
	{
		return $this->add(['GET'], $path, function () use ($url, $code) {
			\header('Location: ' . $url, true, $code);
		});
	}

	/**
	 * @param string                     $base_path
	 * @param \Framework\Routing\Route[] $routes
	 * @param array                      $options
	 *
	 * @return array
	 */
	public function group(string $base_path, array $routes, array $options = []) : array
	{
		$base_path = \rtrim($base_path, '/');
		foreach ($routes as $route) {
			if (\is_array($route)) {
				$this->group($base_path, $route, $options);
				continue;
			}
			$route->setPath($base_path . $route->getPath());
			if ($options) {
				$specific_options = $options;
				if ($route->getOptions()) {
					$specific_options = \array_replace_recursive($options, $route->getOptions());
				}
				$route->setOptions($specific_options);
			}
		}
		return $routes;
	}

	public function namespace(string $namespace, array $routes) : array
	{
		$namespace = \trim($namespace, '\\');
		foreach ($routes as $route) {
			if (\is_array($route)) {
				$this->namespace($namespace, $route);
				continue;
			}
			if (\is_string($route->getAction())) {
				$route->setAction($namespace . '\\' . $route->getAction());
			}
		}
		return $routes;
	}

	public function resource(
		string $path,
		string $class,
		string $base_name,
		array $except = [],
		string $placeholder = '{num}'
	) : array {
		$path = \rtrim($path, '/') . '/';
		$class .= '::';
		if ($except) {
			$except = \array_flip($except);
		}
		$routes = [];
		if ( ! isset($except['index'])) {
			$routes[] = $this->get(
				$path,
				$class . 'index',
				$base_name . '.index'
			);
		}
		if ( ! isset($except['create'])) {
			$routes[] = $this->post(
				$path,
				$class . 'create',
				$base_name . '.create'
			);
		}
		if ( ! isset($except['show'])) {
			$routes[] = $this->get(
				$path . $placeholder,
				$class . 'show/0',
				$base_name . '.show'
			);
		}
		if ( ! isset($except['update'])) {
			$routes[] = $this->patch(
				$path . $placeholder,
				$class . 'update/0',
				$base_name . '.update'
			);
		}
		if ( ! isset($except['replace'])) {
			$routes[] = $this->put(
				$path . $placeholder,
				$class . 'replace/0',
				$base_name . '.replace'
			);
		}
		if ( ! isset($except['delete'])) {
			$routes[] = $this->delete(
				$path . $placeholder,
				$class . 'delete/0',
				$base_name . '.delete'
			);
		}
		return $routes;
	}

	public function webResource(
		string $path,
		string $class,
		string $base_name,
		array $except = [],
		string $placeholder = '{num}'
	) : array {
		$routes = $this->resource($path, $class, $base_name, $except, $placeholder);
		$path = \rtrim($path, '/') . '/';
		$class .= '::';
		if ($except) {
			$except = \array_flip($except);
		}
		if ( ! isset($except['web_new'])) {
			$routes[] = $this->get(
				$path . 'new',
				$class . 'new',
				$base_name . '.web_new'
			);
		}
		if ( ! isset($except['web_edit'])) {
			$routes[] = $this->get(
				$path . $placeholder . '/edit',
				$class . 'edit/0',
				$base_name . '.web_edit'
			);
		}
		if ( ! isset($except['web_delete'])) {
			$routes[] = $this->post(
				$path . $placeholder . '/delete',
				$class . 'delete/0',
				$base_name . '.web_delete'
			);
		}
		if ( ! isset($except['web_update'])) {
			$routes[] = $this->post(
				$path . $placeholder . '/update',
				$class . 'update/0',
				$base_name . '.web_update'
			);
		}
		return $routes;
	}
}
