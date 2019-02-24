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

	public function add($methods, string $path, $function) : Route
	{
		$route = new Route($this, $path, $function);
		$methods = (array) $methods;
		foreach ($methods as $method) {
			$this->addRoute($method, $route);
		}
		return $route;
	}

	public function get(string $path, $function) : Route
	{
		return $this->add('GET', $path, $function);
	}

	public function post(string $path, $function) : Route
	{
		return $this->add('POST', $path, $function);
	}

	public function put(string $path, $function) : Route
	{
		return $this->add('PUT', $path, $function);
	}

	public function patch(string $path, $function) : Route
	{
		return $this->add('PATCH', $path, $function);
	}

	public function delete(string $path, $function) : Route
	{
		return $this->add('DELETE', $path, $function);
	}
}
