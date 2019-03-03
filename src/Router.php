<?php namespace Framework\Routing;

class Router
{
	protected $defaultRouteNamespace;
	protected $defaultRouteFunction = 'index';
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
	protected $matchedBaseURL;
	/**
	 * @var array
	 */
	protected $matchedBaseURLParams = [];
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

	/*public function getDefaultRouteNotFound() : Route
	{
		if ($this->defaultRouteNotFound) {
			return $this->defaultRouteNotFound;
		}
		$this->defaultRouteNotFound=new Route()
	}*/
	public function getDefaultRouteFunction() : string
	{
		return $this->defaultRouteFunction;
	}

	public function setDefaultRouteFunction(string $function)
	{
		$this->defaultRouteFunction = $function;
		return $this;
	}

	protected function getDefaultRouteNotFound() : Route
	{
		return (new Route(
			$this,
			$this->getMatchedBaseURL(),
			$this->getMatchedPath(),
			$this->defaultRouteNotFound ?? function () {
				\http_response_code(404);
			}
		))->setName('not-found');
	}

	public function setDefaultRouteNotFound($function)
	{
		$this->defaultRouteNotFound = $function;
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
			return $string;
		}
		foreach ($matches[0] as $index => $pattern) {
			if ( ! isset($params[$index])) {
				throw new \InvalidArgumentException("Parameter is empty. Index: {$index}");
			}
			if ( ! \preg_match('#' . $pattern . '#', $params[$index])) {
				throw new \InvalidArgumentException('Invalid parameters');
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

	public function serve(string $base_url, callable $callable) : void
	{
		$collection = new Collection($this, $base_url);
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

	/**
	 * @return \Framework\Routing\Route|null
	 */
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
		return $this->getMatchedBaseURL() ?
			$this->getMatchedBaseURL() . $this->getMatchedPath()
			: null;
	}

	public function getMatchedBaseURL() : ?string
	{
		return $this->matchedBaseURL;
	}

	protected function setMatchedBaseURL(string $base_url)
	{
		$this->matchedBaseURL = $base_url;
	}

	public function getMatchedBaseURLParams() : array
	{
		return $this->matchedBaseURLParams;
	}

	protected function setMatchedBaseURLParams(array $params)
	{
		$this->matchedBaseURLParams = $params;
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

	protected function renderBaseURL(array $parsed_url) : string
	{
		$base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
		if (isset($parsed_url['port']) && ! \in_array($parsed_url['port'], [null, 80, 443], true)) {
			$base_url .= ':' . $parsed_url['port'];
		}
		return $base_url;
	}

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
			throw new \InvalidArgumentException('Invalid HTTP method: ' . $method);
		}
		if ( ! \filter_var($url, \FILTER_VALIDATE_URL)) {
			throw new \InvalidArgumentException('Invalid URL: ' . $url);
		}
		$parsed_url = $this->parseURL($url);
		$this->setMatchedPath($parsed_url['path']);
		$base_url = $this->renderBaseURL($parsed_url);
		$this->setMatchedBaseURL($base_url);
		$collection = $this->matchCollection($base_url);
		if ( ! $collection) {
			// ROUTER ERROR 404
			return $this->matchedRoute = $this->getDefaultRouteNotFound();
		}
		$route = $this->matchRoute($method, $collection, $parsed_url['path']);
		if ( ! $route) {
			if ($method === 'OPTIONS' && $this->isAutoOptions()) {
				$route = $this->getOptionsRoute($collection);
			}
			if ( ! $route) {
				// COLLECTION ERROR 404
				$route = $collection->getRouteNotFound() ?? $this->getDefaultRouteNotFound();
			}
		}
		return $this->matchedRoute = $route;
	}

	protected function matchCollection(string $base_url) : ?Collection
	{
		foreach ($this->getCollections() as $collection) {
			$pattern = $this->replacePlaceholders($collection->baseURL);
			$matched = \preg_match(
				'#^' . $pattern . '$#',
				$base_url,
				$matches
			);
			if ($matched) {
				$this->setMatchedBaseURL($matches[0]);
				unset($matches[0]);
				$this->setMatchedBaseURLParams(\array_values($matches));
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
				$route->setFunctionParams($this->getMatchedPathParams());
				//$this->matchedURL = $this->matchedBaseURL . $this->matchedRoutePath;
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

	protected function getOptionsRoute(Collection $collection) : ?Route
	{
		$allowed = $this->getAllowedMethods($collection);
		return empty($allowed)
			? null
			: (new Route(
				$this,
				$this->getMatchedBaseURL(),
				$this->getMatchedPath(),
				function () use ($allowed) {
					\http_response_code(200);
					\header('Allow: ' . \implode(', ', $allowed));
				}
			))->setName('auto-options');
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
			$allowed[] = 'OPTIONS';
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
