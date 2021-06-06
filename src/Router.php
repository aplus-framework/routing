<?php namespace Framework\Routing;

use Closure;
use Framework\HTTP\Response;
use Framework\Language\Language;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class Router.
 */
class Router
{
	protected string $defaultRouteActionMethod = 'index';
	protected Closure | string $defaultRouteNotFound;
	/**
	 * @var array|string[]
	 */
	protected static array $placeholders = [
		'{alpha}' => '([a-zA-Z]+)',
		'{alphanum}' => '([a-zA-Z0-9]+)',
		'{any}' => '(.*)',
		'{hex}' => '([[:xdigit:]]+)',
		'{int}' => '([0-9]{1,18}+)',
		'{md5}' => '([a-f0-9]{32}+)',
		'{num}' => '([0-9]+)',
		'{port}' => '([0-9]{1,4}|[1-5][0-9]{4}|6[0-4][0-9]{3}|65[0-4][0-9]{2}|655[0-2][0-9]|6553[0-5])',
		'{scheme}' => '(https?)',
		'{segment}' => '([^/]+)',
		'{subdomain}' => '([^.]+)',
		//'{subdomain}' => '([A-Za-z0-9](?:[a-zA-Z0-9\-]{0,61}[A-Za-z0-9])?)',
		'{title}' => '([a-zA-Z0-9_-]+)',
	];
	/**
	 * @var Collection[]
	 */
	protected array $collections = [];
	protected ?Route $matchedRoute = null;
	protected ?string $matchedOrigin = null;
	/**
	 * @var array|string[]
	 */
	protected array $matchedOriginParams = [];
	protected ?string $matchedPath = null;
	/**
	 * @var array|string[]
	 */
	protected array $matchedPathParams = [];
	protected bool $autoOptions = false;
	protected bool $autoMethods = false;
	protected Response $response;
	protected Language $language;

	public function __construct(Response $response, Language $language = null)
	{
		$this->response = $response;
		if ($language === null) {
			$language = new Language('en');
		}
		$this->language = $language;
		$this->language->addDirectory(__DIR__ . '/Languages');
	}

	/**
	 * @return \Framework\HTTP\Response
	 */
	public function getResponse() : Response
	{
		return $this->response;
	}

	public function getDefaultRouteActionMethod() : string
	{
		return $this->defaultRouteActionMethod;
	}

	/**
	 * @param string $action
	 *
	 * @return $this
	 */
	public function setDefaultRouteActionMethod(string $action)
	{
		$this->defaultRouteActionMethod = $action;
		return $this;
	}

	protected function getDefaultRouteNotFound() : Route
	{
		$router = $this;
		return (new Route(
			$this,
			$this->getMatchedOrigin(),
			$this->getMatchedPath(),
			$this->defaultRouteNotFound ?? static function () use ($router) {
				$router->response->setStatusLine(404);
				if ($router->response->getRequest()->isJSON()) {
					return $router->response->setJSON([
						'error' => [
							'code' => 404,
							'reason' => 'Not Found',
						],
					]);
				}
				$lang = $router->language->getCurrentLocale();
				$title = $router->language->render('routing', 'error404');
				$message = $router->language->render('routing', 'pageNotFound');
				return $router->response->setBody(
					<<<HTML
					<!doctype html>
					<html lang="{$lang}">
					<head>
						<meta charset="utf-8">
						<title>{$title}</title>
					</head>
					<body>
					<h1>{$title}</h1>
					<p>{$message}</p>
					</body>
					</html>

					HTML
				);
			}
		))->setName('not-found');
	}

	/**
	 * @param Closure|string $action
	 *
	 * @return $this
	 */
	public function setDefaultRouteNotFound(Closure | string $action)
	{
		$this->defaultRouteNotFound = $action;
		return $this;
	}

	/**
	 * @param array|string|string[] $placeholder
	 * @param string|null           $pattern
	 *
	 * @return $this
	 */
	public function addPlaceholder(array | string $placeholder, string $pattern = null)
	{
		if (\is_array($placeholder)) {
			foreach ($placeholder as $key => $value) {
				static::$placeholders['{' . $key . '}'] = $value;
			}
			return $this;
		}
		static::$placeholders['{' . $placeholder . '}'] = $pattern;
		return $this;
	}

	/**
	 * @return array|string[]
	 */
	public function getPlaceholders() : array
	{
		return static::$placeholders;
	}

	public function replacePlaceholders(string $string, bool $flip = false) : string
	{
		$placeholders = $this->getPlaceholders();
		if ($flip) {
			$placeholders = \array_flip($placeholders);
		}
		return \strtr($string, $placeholders);
	}

	/**
	 * @param string $string
	 * @param string ...$params
	 *
	 * @throws InvalidArgumentException if param not required, empty or invalid
	 *
	 * @return string
	 */
	public function fillPlaceholders(string $string, string ...$params) : string
	{
		$string = $this->replacePlaceholders($string);
		\preg_match_all('#\(([^)]+)\)#', $string, $matches);
		if (empty($matches[0])) {
			if ($params) {
				throw new InvalidArgumentException(
					'String has no placeholders. Parameters not required'
				);
			}
			return $string;
		}
		foreach ($matches[0] as $index => $pattern) {
			if ( ! isset($params[$index])) {
				throw new InvalidArgumentException("Placeholder parameter is empty: {$index}");
			}
			if ( ! \preg_match('#' . $pattern . '#', $params[$index])) {
				throw new InvalidArgumentException("Placeholder parameter is invalid: {$index}");
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
	 * @param string|null $origin   URL Origin. A string in the following format:
	 *                              {scheme}://{hostname}[:{port}]. Null to auto-detect.
	 * @param callable    $callable A function receiving an instance of Collection as the first
	 *                              parameter
	 */
	public function serve(?string $origin, callable $callable) : void
	{
		if ($origin === null) {
			$origin = $this->response->getRequest()->getURL()->getOrigin();
		}
		$collection = new Collection($this, $origin);
		$callable($collection);
		$this->addCollection($collection);
	}

	/**
	 * @param Collection $collection
	 *
	 * @return $this
	 */
	protected function addCollection(Collection $collection)
	{
		$this->collections[] = $collection;
		return $this;
	}

	/**
	 * @return Collection[]
	 */
	public function getCollections() : array
	{
		return $this->collections;
	}

	public function getMatchedRoute() : ?Route
	{
		return $this->matchedRoute;
	}

	/**
	 * @param Route $route
	 *
	 * @return $this
	 */
	protected function setMatchedRoute(Route $route)
	{
		$this->matchedRoute = $route;
		return $this;
	}

	public function getMatchedPath() : ?string
	{
		return $this->matchedPath;
	}

	/**
	 * @param string $path
	 *
	 * @return $this
	 */
	protected function setMatchedPath(string $path)
	{
		$this->matchedPath = $path;
		return $this;
	}

	/**
	 * @return array|string[]
	 */
	public function getMatchedPathParams() : array
	{
		return $this->matchedPathParams;
	}

	/**
	 * @param array|string[] $params
	 *
	 * @return $this
	 */
	protected function setMatchedPathParams(array $params)
	{
		$this->matchedPathParams = $params;
		return $this;
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

	/**
	 * @param string $origin
	 *
	 * @return $this
	 */
	protected function setMatchedOrigin(string $origin)
	{
		$this->matchedOrigin = $origin;
		return $this;
	}

	/**
	 * @return array|string[]
	 */
	public function getMatchedOriginParams() : array
	{
		return $this->matchedOriginParams;
	}

	/**
	 * @param array|string[] $params
	 *
	 * @return $this
	 */
	protected function setMatchedOriginParams(array $params)
	{
		$this->matchedOriginParams = $params;
		return $this;
	}

	/**
	 * Match HTTP Method and URL against Collections to process the request.
	 *
	 * @see serve
	 *
	 * @return Route Always returns a Route, even if it is the Route Not Found
	 */
	public function match() : Route
	{
		$method = $this->response->getRequest()->getMethod();
		if ($method === 'HEAD') {
			$method = 'GET';
		}
		$url = $this->response->getRequest()->getURL();
		$this->setMatchedPath($url->getPath());
		$this->setMatchedOrigin($url->getOrigin());
		$collection = $this->matchCollection($url->getOrigin());
		if ( ! $collection) {
			return $this->matchedRoute = $this->getDefaultRouteNotFound();
		}
		return $this->matchedRoute = $this->matchRoute($method, $collection, $url->getPath())
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
				unset($matches[0]);
				$this->setMatchedPathParams(\array_values($matches));
				$route->setActionParams($this->getMatchedPathParams());
				return $route;
			}
		}
		return null;
	}

	/**
	 * Enable/disable the feature of auto detect and show HTTP allowed methods
	 * via the Allow header when the request has the OPTIONS method.
	 *
	 * @param bool $enabled true to enable, false to disable
	 *
	 * @return $this
	 */
	public function setAutoOptions(bool $enabled = true)
	{
		$this->autoOptions = $enabled;
		return $this;
	}

	public function isAutoOptions() : bool
	{
		return $this->autoOptions;
	}

	/**
	 * Enable/disable the feature of auto detect and show HTTP allowed methods
	 * via the Allow header when a route with the request method does not exist.
	 *
	 * A response with code 405 "Method Not Allowed" will trigger.
	 *
	 * @param bool $enabled true to enable, false to disable
	 *
	 * @return $this
	 */
	public function setAutoMethods(bool $enabled = true)
	{
		$this->autoMethods = $enabled;
		return $this;
	}

	public function isAutoMethods() : bool
	{
		return $this->autoMethods;
	}

	protected function getRouteWithAllowHeader(Collection $collection, int $code) : ?Route
	{
		$allowed = $this->getAllowedMethods($collection);
		$response = $this->response;
		return empty($allowed)
			? null
			: (new Route(
				$this,
				$this->getMatchedOrigin(),
				$this->getMatchedPath(),
				static function () use ($allowed, $code, $response) {
					$response->setStatusLine($code);
					$response->setHeader('Allow', \implode(', ', $allowed));
				}
			))->setName('auto-allow-' . $code);
	}

	/**
	 * @param Collection $collection
	 *
	 * @return array|string[]
	 */
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

	/**
	 * Gets a named route.
	 *
	 * @param string $name
	 *
	 * @throws RuntimeException if named route not found
	 *
	 * @return Route
	 */
	public function getNamedRoute(string $name) : Route
	{
		foreach ($this->getCollections() as $collection) {
			foreach ($collection->routes as $routes) {
				foreach ($routes as $route) {
					/**
					 * @var Route $route
					 */
					if ($route->getName() === $name) {
						return $route;
					}
				}
			}
		}
		throw new RuntimeException('Named route not found: ' . $name);
	}

	/**
	 * Tells if has a named route.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function hasNamedRoute(string $name) : bool
	{
		foreach ($this->getCollections() as $collection) {
			foreach ($collection->routes as $routes) {
				foreach ($routes as $route) {
					/**
					 * @var Route $route
					 */
					if ($route->getName() === $name) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * @return array|Route[]
	 */
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
