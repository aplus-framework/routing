<?php namespace Framework\Routing;

class Router
{
	protected $defaultRouteNamespace;
	protected $defaultRouteActionMethod = 'index';
	/**
	 * @var callable
	 */
	protected $defaultRouteNotFound;
	protected $placeholders = [
		'{alpha}' => '([a-zA-Z]+)',
		'{alphanum}' => '([a-zA-Z0-9]+)',
		'{any}' => '(.*)',
		'{num}' => '([0-9]+)',
		'{port}' => '([0-9]{1,4}|[1-5][0-9]{4}|6[0-4][0-9]{3}|65[0-4][0-9]{2}|655[0-2][0-9]|6553[0-5])',
		'{scheme}' => '(https?)',
		'{segment}' => '([^/]+)',
		'{subdomain}' => '([^.]+)',
		//'{subdomain}' => '([A-Za-z0-9](?:[a-zA-Z0-9\-]{0,61}[A-Za-z0-9])?)',
		'{title}' => '([a-zA-Z0-9_-]+)',
	];
	/**
	 * @var \Framework\Routing\Collection[]
	 */
	protected $collections = [];
	/**
	 * @var \Framework\Routing\Route|null
	 */
	protected $matchedRoute;
	/**
	 * @var string|null
	 */
	protected $matchedOrigin;
	/**
	 * @var array
	 */
	protected $matchedOriginParams = [];
	/**
	 * @var string|null
	 */
	protected $matchedPath;
	/**
	 * @var array
	 */
	protected $matchedPathParams = [];
	/**
	 * @var bool
	 */
	protected $autoOptions = false;
	/**
	 * @var bool
	 */
	protected $autoMethods = false;

	/*public function getDefaultRouteNotFound() : Route
	{
		if ($this->defaultRouteNotFound) {
			return $this->defaultRouteNotFound;
		}
		$this->defaultRouteNotFound=new Route()
	}*/
	public function getDefaultRouteActionMethod() : string
	{
		return $this->defaultRouteActionMethod;
	}

	public function setDefaultRouteActionMethod(string $action)
	{
		$this->defaultRouteActionMethod = $action;
		return $this;
	}

	protected function getDefaultRouteNotFound() : Route
	{
		return (new Route(
			$this,
			$this->getMatchedOrigin(),
			$this->getMatchedPath(),
			$this->defaultRouteNotFound ?? function () {
				\http_response_code(404);
			}
		))->setName('not-found');
	}

	public function setDefaultRouteNotFound($action)
	{
		$this->defaultRouteNotFound = $action;
		return $this;
	}

	/**
	 * @param array|string $placeholder
	 * @param string|null  $pattern
	 *
	 * @return $this
	 */
	public function addPlaceholder($placeholder, string $pattern = null)
	{
		if (\is_array($placeholder)) {
			foreach ($placeholder as $key => $value) {
				$this->placeholders['{' . $key . '}'] = $value;
			}
			return $this;
		}
		$this->placeholders['{' . $placeholder . '}'] = $pattern;
		return $this;
	}

	public function getPlaceholders() : array
	{
		return $this->placeholders;
	}

	public function replacePlaceholders(string $string, bool $flip = false) : string
	{
		$placeholders = $this->getPlaceholders();
		if ($flip) {
			$placeholders = \array_flip($placeholders);
		}
		return \strtr($string, $placeholders);
	}

	public function fillPlaceholders(string $string, ...$params) : string
	{
		$string = $this->replacePlaceholders($string);
		\preg_match_all('#\(([^)]+)\)#', $string, $matches);
		if (empty($matches[0])) {
			if ($params) {
				throw new \InvalidArgumentException(
					'String has not placeholders. Parameters not required'
				);
			}
			return $string;
		}
		foreach ($matches[0] as $index => $pattern) {
			if ( ! isset($params[$index])) {
				throw new \InvalidArgumentException("Parameter is empty. Index: {$index}");
			}
			if ( ! \preg_match('#' . $pattern . '#', $params[$index])) {
				throw new \InvalidArgumentException("Parameter is invalid. Index: {$index}");
			}
			$string = \substr_replace(
				$string,
				$params[$index],
				\strpos($string, $pattern),
				\strlen($pattern)
			);
		}
		return $string;
	}

	/**
	 * Serves a Collection of Routes to a specific Origin.
	 *
	 * @param string   $origin   URL Origin. A string in the following format:
	 *                           {scheme}://{hostname}[:{port}]
	 * @param callable $callable A function receiving an instance of Collection as the first
	 *                           parameter
	 */
	public function serve(string $origin, callable $callable) : void
	{
		$collection = new Collection($this, $origin);
		$callable($collection);
		$this->addCollection($collection);
	}

	protected function addCollection(Collection $collection)
	{
		$this->collections[] = $collection;
	}

	/**
	 * @return \Framework\Routing\Collection[]
	 */
	public function getCollections() : array
	{
		return $this->collections;
	}

	public function getMatchedRoute() : ?Route
	{
		return $this->matchedRoute;
	}

	protected function setMatchedRoute(Route $route)
	{
		$this->matchedRoute = $route;
	}

	public function getMatchedPath() : ?string
	{
		return $this->matchedPath;
	}

	protected function setMatchedPath(string $path)
	{
		$this->matchedPath = $path;
	}

	public function getMatchedPathParams() : array
	{
		return $this->matchedPathParams;
	}

	protected function setMatchedPathParams(array $params)
	{
		$this->matchedPathParams = $params;
	}

	public function getMatchedURL() : ?string
	{
		return $this->getMatchedOrigin() ?
			$this->getMatchedOrigin() . $this->getMatchedPath()
			: null;
	}

	public function getMatchedOrigin() : ?string
	{
		return $this->matchedOrigin;
	}

	protected function setMatchedOrigin(string $origin)
	{
		$this->matchedOrigin = $origin;
	}

	public function getMatchedOriginParams() : array
	{
		return $this->matchedOriginParams;
	}

	protected function setMatchedOriginParams(array $params)
	{
		$this->matchedOriginParams = $params;
	}

	protected function parseURL(string $url) : array
	{
		$parsed = \parse_url($url);
		$parsed = \array_replace([
			'scheme' => 'http',
			'host' => null,
			'port' => null,
			'path' => '/',
		], $parsed);
		$parsed['path'] = '/' . \trim($parsed['path'], '/');
		return $parsed;
	}

	/**
	 * @param array $parsed_url
	 *
	 * @see parseURL()
	 *
	 * @return string
	 */
	protected function renderOrigin(array $parsed_url) : string
	{
		$origin = $parsed_url['scheme'] . '://' . $parsed_url['host'];
		if (isset($parsed_url['port']) && ! \in_array($parsed_url['port'], [null, 80, 443], true)) {
			$origin .= ':' . $parsed_url['port'];
		}
		return $origin;
	}

	/**
	 * Match HTTP Method and URL against Collections to process the request.
	 *
	 * @param string $method HTTP Method. One of: GET, HEAD, POST, PUT, PATCH, DELETE, OPTIONS
	 * @param string $url    The requested URL
	 *
	 * @see serve()
	 *
	 * @return \Framework\Routing\Route Always returns a Route, even if it is the Route Not Found
	 */
	public function match(string $method, string $url) : Route
	{
		$method = \strtoupper($method);
		if ($method === 'HEAD') {
			$method = 'GET';
		} elseif ( ! \in_array(
			$method,
			['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
			true
		)) {
			\http_response_code(405);
			\header('Allow: GET, DELETE, HEAD, OPTIONS, PATCH, POST, PUT');
			throw new \InvalidArgumentException('Invalid HTTP method: ' . $method);
		}
		if ( ! \filter_var($url, \FILTER_VALIDATE_URL)) {
			\http_response_code(400);
			throw new \InvalidArgumentException('Invalid URL: ' . $url);
		}
		$parsed_url = $this->parseURL($url);
		$this->setMatchedPath($parsed_url['path']);
		$origin = $this->renderOrigin($parsed_url);
		$this->setMatchedOrigin($origin);
		$collection = $this->matchCollection($origin);
		if ( ! $collection) {
			return $this->matchedRoute = $this->getDefaultRouteNotFound();
		}
		return $this->matchedRoute = $this->matchRoute($method, $collection, $parsed_url['path'])
			?? $this->getAlternativeRoute($method, $collection);
	}

	protected function getAlternativeRoute(string $method, Collection $collection) : Route
	{
		if ($method === 'OPTIONS' && $this->isAutoOptions()) {
			$route = $this->getRouteWithAllowHeader($collection, 200);
		} elseif ($this->isAutoMethods()) {
			$route = $this->getRouteWithAllowHeader($collection, 405);
		}
		if (empty($route)) {
			$route = $collection->getRouteNotFound() ?? $this->getDefaultRouteNotFound();
		}
		return $route;
	}

	protected function matchCollection(string $origin) : ?Collection
	{
		foreach ($this->getCollections() as $collection) {
			$pattern = $this->replacePlaceholders($collection->origin);
			$matched = \preg_match(
				'#^' . $pattern . '$#',
				$origin,
				$matches
			);
			if ($matched) {
				$this->setMatchedOrigin($matches[0]);
				unset($matches[0]);
				$this->setMatchedOriginParams(\array_values($matches));
				return $collection;
			}
		}
		return null;
	}

	protected function matchRoute(string $method, Collection $collection, string $path) : ?Route
	{
		$routes = $collection->routes;
		if (empty($routes[$method])) {
			return null;
		}
		/**
		 * @var Route $route
		 */
		foreach ($routes[$method] as $route) {
			$pattern = $this->replacePlaceholders($route->getPath());
			$matched = \preg_match(
				'#^' . $pattern . '$#',
				$path,
				$matches
			);
			if ($matched) {
				$this->setMatchedRoute($route);
				//$this->setMatchedRoutePath($matches[0]);
				unset($matches[0]);
				$this->setMatchedPathParams(\array_values($matches));
				$route->setActionParams($this->getMatchedPathParams());
				//$this->matchedURL = $this->matchedOrigin . $this->matchedRoutePath;
				return $route;
			}
		}
		return null;
	}

	public function setAutoOptions(bool $status)
	{
		$this->autoOptions = $status;
		return $this;
	}

	public function isAutoOptions() : bool
	{
		return $this->autoOptions;
	}

	public function setAutoMethods(bool $status)
	{
		$this->autoMethods = $status;
		return $this;
	}

	public function isAutoMethods() : bool
	{
		return $this->autoMethods;
	}

	protected function getRouteWithAllowHeader(Collection $collection, int $code) : ?Route
	{
		$allowed = $this->getAllowedMethods($collection);
		return empty($allowed)
			? null
			: (new Route(
				$this,
				$this->getMatchedOrigin(),
				$this->getMatchedPath(),
				function () use ($allowed, $code) {
					\http_response_code($code);
					\header('Allow: ' . \implode(', ', $allowed));
				}
			))->setName('auto-allow-' . $code);
	}

	protected function getAllowedMethods(Collection $collection) : array
	{
		$allowed = [];
		foreach ($collection->routes as $method => $routes) {
			foreach ($routes as $route) {
				$pattern = $this->replacePlaceholders($route->getPath());
				$matched = \preg_match(
					'#^' . $pattern . '$#',
					$this->getMatchedPath()
				);
				if ($matched) {
					$allowed[] = $method;
					continue 2;
				}
			}
		}
		if ($allowed) {
			if (\in_array('GET', $allowed, true)) {
				$allowed[] = 'HEAD';
			}
			if ($this->isAutoOptions()) {
				$allowed[] = 'OPTIONS';
			}
			$allowed = \array_unique($allowed);
			\sort($allowed);
		}
		return $allowed;
	}

	public function getNamedRoute(string $name) : ?Route
	{
		foreach ($this->getCollections() as $collection) {
			foreach ($collection->routes as $routes) {
				foreach ($routes as $route) {
					/**
					 * @var \Framework\Routing\Route $route
					 */
					if ($route->getName() === $name) {
						return $route;
					}
				}
			}
		}
		return null;
	}

	public function getRoutes() : array
	{
		$result = [];
		foreach ($this->getCollections() as $collection) {
			foreach ($collection->routes as $method => $routes) {
				foreach ($routes as $route) {
					$result[$method][] = $route;
				}
			}
		}
		return $result;
	}
}
