<?php namespace Framework\Routing;

class Router
{
	protected $defaultRouteNamespace;
	protected $defaultRouteFunction = 'index';
	protected $defaultRouteNotFound;
	protected $placeholders = [
		'{alpha}' => '([a-zA-Z]+)',
		'{alphanum}' => '([a-zA-Z0-9]+)',
		'{alphacharsssnum}' => '([a-zA-Z0-9]+)', //todo
		'{any}' => '(.*)',
		'{num}' => '([0-9]+)',
		'{scheme}' => '(https?)',
		'{segment}' => '([^/]+)',
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
	 * @var array
	 */
	protected $matchedRouteParams = [];
	/*public function getDefaultRouteNotFound() : Route
	{
		if ($this->defaultRouteNotFound) {
			return $this->defaultRouteNotFound;
		}
		$this->defaultRouteNotFound=new Route()
	}*/

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
				throw new \Exception('Empty params');
			}
			if ( ! \preg_match('#' . $pattern . '#', $params[$index])) {
				throw new \Exception('Invalid params');
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
	protected function getCollections() : array
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

	public function match(string $method, string $url)
	{
		$method = \strtoupper($method);
		if ($method === 'HEAD') {
			$method = 'GET';
		} elseif ( ! \in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
			throw new \InvalidArgumentException('Invalid HTTP method: ' . $method);
		}
		if ( ! \filter_var($url, \FILTER_VALIDATE_URL)) {
			throw new \InvalidArgumentException('Invalid URL: ' . $url);
		}
		$parsed_url = $this->parseURL($url);
		$base_url = $this->renderBaseURL($parsed_url);
		$collection = $this->matchCollection($base_url);
		if ( ! $collection) {
			// ROUTER ERROR 404
			return $this->defaultRouteNotFound;
		}
		$route = $this->matchRoute($method, $collection, $parsed_url['path']);
		if ( ! $route) {
			// COLLECTION ERROR 404
			return $collection->getRouteNotFound() ?? $this->defaultRouteNotFound;
		}
		return $this->matchedRoute = $route;
	}

	protected function matchCollection(string $base_url) : ?Collection
	{
		foreach ($this->getCollections() as $collection) {
			$pattern = $this->replacePlaceholders($collection->getBaseURL());
			$matched = \preg_match(
				'#^' . $pattern . '$#',
				$base_url,
				$matches
			);
			if ($matched) {
				//$this->matchedBaseURL = $matches[0];
				return $collection;
			}
		}
		return null;
	}

	protected function matchRoute(string $method, Collection $collection, string $path) : ?Route
	{
		$routes = $collection->getRoutes();
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
				$this->matchedRoute = $route;
				//$this->matchedRoutePath = $matches[0];
				unset($matches[0]);
				//$this->matchedRouteParams = \array_values($matches);
				$route->setFunctionParams(\array_values($matches));
				//$this->matchedURL = $this->matchedBaseURL . $this->matchedRoutePath;
				return $route;
			}
		}
		return null;
	}

	public function getNamedRoute(string $name) : ?Route
	{
		foreach ($this->getCollections() as $collection) {
			foreach ($collection->getRoutes() as $routes) {
				/**
				 * @var Route $route
				 */
				foreach ($routes as $route) {
					if ($route->getName() === $name) {
						return $route;
					}
				}
			}
		}
		return null;
	}
}
