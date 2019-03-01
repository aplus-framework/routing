<?php namespace Framework\Routing;

class Collection
{
	protected $router;
	protected $baseURL;
	protected $routes = [];
	protected $routeNotFound;

	public function __construct(Router $router, string $base_url)
	{
		$this->router = $router;
		$this->setBaseURL($base_url);
	}

	protected function setBaseURL(string $base_url)
	{
		$this->baseURL = \ltrim($base_url, '/');
	}

	protected function addRoute(string $method, Route $route)
	{
		$this->routes[$method][] = $route;
	}

	public function getRouter() : Router
	{
		return $this->router;
	}

	/**
	 * @return array
	 */
	public function getRoutes() : array
	{
		return $this->routes;
	}

	public function getRouteNotFound() : ?Route
	{
		return $this->routeNotFound;
	}

	public function getBaseURL() : string
	{
		return $this->baseURL;
	}

	public function add(array $methods, string $path, $function, string $name = null) : Route
	{
		$route = new Route($this, $path, $function);
		if ($name) {
			$route->setName($name);
		}
		foreach ($methods as $method) {
			$this->addRoute($method, $route);
		}
		return $route;
	}

	public function get(string $path, $function, string $name = null) : Route
	{
		return $this->add(['GET'], $path, $function, $name);
	}

	public function post(string $path, $function, string $name = null) : Route
	{
		return $this->add(['POST'], $path, $function, $name);
	}

	public function put(string $path, $function, string $name = null) : Route
	{
		return $this->add(['PUT'], $path, $function, $name);
	}

	public function patch(string $path, $function, string $name = null) : Route
	{
		return $this->add(['PATCH'], $path, $function, $name);
	}

	public function delete(string $path, $function, string $name = null) : Route
	{
		return $this->add(['DELETE'], $path, $function, $name);
	}

	/**
	 * @param string  $base_path
	 * @param Route[] $routes
	 * @param array   $options
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
				$route->addOptions($options);
			}
		}
		return $routes;
	}
}
