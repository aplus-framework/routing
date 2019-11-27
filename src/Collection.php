<?php namespace Framework\Routing;

/**
 * Class Collection.
 */
class Collection implements \Countable
{
	/**
	 * @var Router
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

	/**
	 * Collection constructor.
	 *
	 * @param Router $router A Router instance
	 * @param string $origin URL Origin. A string in the following format:
	 *                       {scheme}://{hostname}[:{port}]
	 */
	public function __construct(Router $router, string $origin)
	{
		$this->router = $router;
		$this->setOrigin($origin);
	}

	public function __call($method, $arguments)
	{
		if ($method === 'getRouteNotFound') {
			return $this->getRouteNotFound();
		}
		if (\method_exists($this, $method)) {
			throw new \BadMethodCallException("Method not allowed: {$method}");
		}
		throw new \BadMethodCallException("Method not found: {$method}");
	}

	public function __get($property)
	{
		if ($property === 'origin') {
			return $this->origin;
		}
		if ($property === 'router') {
			return $this->router;
		}
		if ($property === 'routes') {
			return $this->routes;
		}
		if (\property_exists($this, $property)) {
			throw new \LogicException("Property not allowed: {$property}");
		}
		throw new \LogicException("Property not found: {$property}");
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

	/**
	 * Sets the action to the Collection Route Not Found.
	 *
	 * @param \Closure|string $action the Route function to run when no Route path is found for
	 *                                this collection
	 */
	public function notFound($action) : void
	{
		$this->notFound = $action;
	}

	/**
	 * Gets the Route Not Found for this Collection.
	 *
	 * @see notFound
	 *
	 * @return Route|null The Route containing the Not Found Action or null if
	 *                    the Action was not set
	 */
	protected function getRouteNotFound() : ?Route
	{
		return empty($this->notFound)
			? null
			: (new Route(
				$this->router,
				$this->router->getMatchedOrigin(),
				$this->router->getMatchedPath(),
				$this->notFound
			))->setName('collection-not-found');
	}

	/**
	 * Adds a Route to match many HTTP Methods.
	 *
	 * @param array           $http_methods The HTTP Methods
	 * @param string          $path         The URL path
	 * @param \Closure|string $action       The Route action
	 * @param string|null     $name         The Route name
	 *
	 * @return Route
	 */
	public function add(array $http_methods, string $path, $action, string $name = null) : Route
	{
		$route = new Route($this->router, $this->origin, $path, $action);
		if ($name) {
			$route->setName($name);
		}
		foreach ($http_methods as $method) {
			$this->addRoute($method, $route);
		}
		return $route;
	}

	/**
	 * Adds a Route to match the HTTP Method GET.
	 *
	 * @param string          $path   The URL path
	 * @param \Closure|string $action The Route action
	 * @param string|null     $name   The Route name
	 *
	 * @return Route The Route added to the Collection
	 */
	public function get(string $path, $action, string $name = null) : Route
	{
		return $this->add(['GET'], $path, $action, $name);
	}

	/**
	 * Adds a Route to match the HTTP Method POST.
	 *
	 * @param string          $path   The URL path
	 * @param \Closure|string $action The Route action
	 * @param string|null     $name   The Route name
	 *
	 * @return Route The Route added to the Collection
	 */
	public function post(string $path, $action, string $name = null) : Route
	{
		return $this->add(['POST'], $path, $action, $name);
	}

	/**
	 * Adds a Route to match the HTTP Method PUT.
	 *
	 * @param string          $path   The URL path
	 * @param \Closure|string $action The Route action
	 * @param string|null     $name   The Route name
	 *
	 * @return Route The Route added to the Collection
	 */
	public function put(string $path, $action, string $name = null) : Route
	{
		return $this->add(['PUT'], $path, $action, $name);
	}

	/**
	 * Adds a Route to match the HTTP Method PATCH.
	 *
	 * @param string          $path   The URL path
	 * @param \Closure|string $action The Route action
	 * @param string|null     $name   The Route name
	 *
	 * @return Route The Route added to the Collection
	 */
	public function patch(string $path, $action, string $name = null) : Route
	{
		return $this->add(['PATCH'], $path, $action, $name);
	}

	/**
	 * Adds a Route to match the HTTP Method DELETE.
	 *
	 * @param string          $path   The URL path
	 * @param \Closure|string $action The Route action
	 * @param string|null     $name   The Route name
	 *
	 * @return Route The Route added to the Collection
	 */
	public function delete(string $path, $action, string $name = null) : Route
	{
		return $this->add(['DELETE'], $path, $action, $name);
	}

	/**
	 * Adds a Route to match the HTTP Method OPTIONS.
	 *
	 * @param string          $path   The URL path
	 * @param \Closure|string $action The Route action
	 * @param string|null     $name   The Route name
	 *
	 * @return Route The Route added to the Collection
	 */
	public function options(string $path, $action, string $name = null) : Route
	{
		return $this->add(['OPTIONS'], $path, $action, $name);
	}

	/**
	 * Adds a Route to match a URL path and automatically redirects to a URL.
	 *
	 * @param string $path The URL path
	 * @param string $url  The URL to redirect
	 * @param int    $code The status code of the response
	 *
	 * @return Route The Route added to the Collection
	 */
	public function redirect(string $path, string $url, int $code = 302) : Route
	{
		return $this->add(['GET'], $path, function () use ($url, $code) {
			\header('Location: ' . $url, true, $code);
		});
	}

	/**
	 * Groups many Routes into a URL path.
	 *
	 * @param string  $base_path The URL path to group in
	 * @param Route[] $routes    The Routes to be grouped
	 * @param array   $options   Custom options passed to the Routes
	 *
	 * @return Route[] The same $routes with updated paths and options
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

	/**
	 * Updates Routes actions, which are strings, prepending a namespace.
	 *
	 * @param string  $namespace The namespace
	 * @param Route[] $routes    The Routes
	 *
	 * @return Route[] The same $routes with updated actions
	 */
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

	/**
	 * Adds many Routes that can be used as a REST Resource.
	 *
	 * @param string $path        The URL path
	 * @param string $class       The name of the class where the resource will point
	 * @param string $base_name   The base name used as a Route name prefix
	 * @param array  $except      Actions not added. Allowed values are: index, create, show,
	 *                            update, replace and delete
	 * @param string $placeholder The placeholder. Normally it matchs an id, a number
	 *
	 * @return Route[] The Routes added to the Collection
	 */
	public function resource(
		string $path,
		string $class,
		string $base_name,
		array $except = [],
		string $placeholder = '{int}'
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

	/**
	 * Adds many Routes that can be used by a Web User Interface and as a REST Resource.
	 *
	 * @param string $path        The URL path
	 * @param string $class       The name of the class where the resource will point
	 * @param string $base_name   The base name used as a Route name prefix
	 * @param array  $except      Actions not added. Allowed values are: index, create, show,
	 *                            update, replace, delete, web_new, web_edit, web_delete and
	 *                            web_update
	 * @param string $placeholder The placeholder. Normally it matchs an id, a number
	 *
	 * @return Route[] The Routes added to the Collection
	 */
	public function webResource(
		string $path,
		string $class,
		string $base_name,
		array $except = [],
		string $placeholder = '{int}'
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

	/**
	 * Count routes in the Collection.
	 *
	 * @return int
	 */
	public function count() : int
	{
		$count = $this->notFound ? 1 : 0;
		foreach ($this->routes as $routes) {
			$count += \count($routes);
		}
		return $count;
	}
}
