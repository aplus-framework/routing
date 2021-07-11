<?php declare(strict_types=1);
/*
 * This file is part of The Framework Routing Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\Routing;

use Closure;
use Framework\HTTP\Response;
use Framework\Language\Language;
use InvalidArgumentException;
use JetBrains\PhpStorm\Pure;
use RuntimeException;

/**
 * Class Router.
 */
class Router
{
    protected string $defaultRouteActionMethod = 'index';
    protected Closure | string $defaultRouteNotFound;
    /**
     * @var array<string,string>
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
        '{slug}' => '([a-z0-9_-]+)',
        '{subdomain}' => '([^.]+)',
        //'{subdomain}' => '([A-Za-z0-9](?:[a-zA-Z0-9\-]{0,61}[A-Za-z0-9])?)',
        '{title}' => '([a-zA-Z0-9_-]+)',
        '{uuid}' => '([0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}+)',
    ];
    /**
     * @var array<int,RouteCollection>
     */
    protected array $collections = [];
    protected ?Route $matchedRoute = null;
    protected ?string $matchedOrigin = null;
    /**
     * @var array<int,string>
     */
    protected array $matchedOriginArguments = [];
    protected ?string $matchedPath = null;
    /**
     * @var array<int,string>
     */
    protected array $matchedPathArguments = [];
    protected bool $autoOptions = false;
    protected bool $autoMethods = false;
    protected Response $response;
    protected Language $language;

    public function __construct(Response $response, Language $language = null)
    {
        $this->response = $response;
        $this->language = $language ?? new Language('en');
        $this->language->addDirectory(__DIR__ . '/Languages');
    }

    /**
     * @return Response
     */
    #[Pure]
    public function getResponse() : Response
    {
        return $this->response;
    }

    #[Pure]
    public function getDefaultRouteActionMethod() : string
    {
        return $this->defaultRouteActionMethod;
    }

    /**
     * @param string $action
     *
     * @return static
     */
    public function setDefaultRouteActionMethod(string $action) : static
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
                $this->response->setStatusLine($this->response::CODE_NOT_FOUND);
                if ($this->response->getRequest()->isJSON()) {
                    return $this->response->setJSON([
                        'error' => [
                            'code' => $this->response::CODE_NOT_FOUND,
                            'reason' => $this->response::getResponseReason(
                                $this->response::CODE_NOT_FOUND
                            ),
                        ],
                    ]);
                }
                $lang = $this->language->getCurrentLocale();
                $dir = $this->language->getCurrentLocaleDirection();
                $title = $this->language->render('routing', 'error404');
                $message = $this->language->render('routing', 'pageNotFound');
                return $this->response->setBody(
                    <<<HTML
                        <!doctype html>
                        <html lang="{$lang}" dir="{$dir}">
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
     * @return static
     */
    public function setDefaultRouteNotFound(Closure | string $action) : static
    {
        $this->defaultRouteNotFound = $action;
        return $this;
    }

    /**
     * @param array<string,string>|string $placeholder
     * @param string|null $pattern
     *
     * @return static
     */
    public function addPlaceholder(array | string $placeholder, string $pattern = null) : static
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
     * @return array<string,string>
     */
    #[Pure]
    public function getPlaceholders() : array
    {
        return static::$placeholders;
    }

    #[Pure]
    public function replacePlaceholders(
        string $string,
        bool $flip = false
    ) : string {
        $placeholders = $this->getPlaceholders();
        if ($flip) {
            $placeholders = \array_flip($placeholders);
        }
        return \strtr($string, $placeholders);
    }

    /**
     * @param string $string
     * @param string ...$arguments
     *
     * @throws InvalidArgumentException if param not required, empty or invalid
     * @throws RuntimeException if a pattern position is not found
     *
     * @return string
     */
    public function fillPlaceholders(string $string, string ...$arguments) : string
    {
        $string = $this->replacePlaceholders($string);
        \preg_match_all('#\(([^)]+)\)#', $string, $matches);
        if (empty($matches[0])) {
            if ($arguments) {
                throw new InvalidArgumentException(
                    'String has no placeholders. Arguments not required'
                );
            }
            return $string;
        }
        foreach ($matches[0] as $index => $pattern) {
            if ( ! isset($arguments[$index])) {
                throw new InvalidArgumentException("Placeholder argument is not set: {$index}");
            }
            if ( ! \preg_match('#' . $pattern . '#', $arguments[$index])) {
                throw new InvalidArgumentException("Placeholder argument is invalid: {$index}");
            }
            $pos = \strpos($string, $pattern);
            if ($pos === false) {
                throw new RuntimeException(
                    "Pattern position not found on placeholder argument: {$index}"
                );
            }
            $string = \substr_replace(
                $string,
                $arguments[$index],
                $pos,
                \strlen($pattern)
            );
        }
        return $string;
    }

    /**
     * Serves a RouteCollection to a specific Origin.
     *
     * @param string|null $origin URL Origin. A string in the following format:
     * {scheme}://{hostname}[:{port}]. Null to auto-detect.
     * @param callable $callable A function receiving an instance of RouteCollection
     * as the first parameter
     */
    public function serve(?string $origin, callable $callable) : void
    {
        if ($origin === null) {
            $origin = $this->response->getRequest()->getURL()->getOrigin();
        }
        $collection = new RouteCollection($this, $origin);
        $callable($collection);
        $this->addCollection($collection);
    }

    /**
     * @param RouteCollection $collection
     *
     * @return static
     */
    protected function addCollection(RouteCollection $collection) : static
    {
        $this->collections[] = $collection;
        return $this;
    }

    /**
     * @return array<int,RouteCollection>
     */
    #[Pure]
    public function getCollections() : array
    {
        return $this->collections;
    }

    #[Pure]
    public function getMatchedRoute() : ?Route
    {
        return $this->matchedRoute;
    }

    /**
     * @param Route $route
     *
     * @return static
     */
    protected function setMatchedRoute(Route $route) : static
    {
        $this->matchedRoute = $route;
        return $this;
    }

    #[Pure]
    public function getMatchedPath() : ?string
    {
        return $this->matchedPath;
    }

    /**
     * @param string $path
     *
     * @return static
     */
    protected function setMatchedPath(string $path) : static
    {
        $this->matchedPath = $path;
        return $this;
    }

    /**
     * @return array<int,string>
     */
    #[Pure]
    public function getMatchedPathArguments() : array
    {
        return $this->matchedPathArguments;
    }

    /**
     * @param array<int,string> $arguments
     *
     * @return static
     */
    protected function setMatchedPathArguments(array $arguments) : static
    {
        $this->matchedPathArguments = $arguments;
        return $this;
    }

    #[Pure]
    public function getMatchedURL() : ?string
    {
        return $this->getMatchedOrigin()
            ? $this->getMatchedOrigin() . $this->getMatchedPath()
            : null;
    }

    #[Pure]
    public function getMatchedOrigin() : ?string
    {
        return $this->matchedOrigin;
    }

    /**
     * @param string $origin
     *
     * @return static
     */
    protected function setMatchedOrigin(string $origin) : static
    {
        $this->matchedOrigin = $origin;
        return $this;
    }

    /**
     * @return array<int,string>
     */
    #[Pure]
    public function getMatchedOriginArguments() : array
    {
        return $this->matchedOriginArguments;
    }

    /**
     * @param array<int,string> $arguments
     *
     * @return static
     */
    protected function setMatchedOriginArguments(array $arguments) : static
    {
        $this->matchedOriginArguments = $arguments;
        return $this;
    }

    /**
     * Match HTTP Method and URL against RouteCollections to process the request.
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

    protected function getAlternativeRoute(string $method, RouteCollection $collection) : Route
    {
        if ($method === 'OPTIONS' && $this->isAutoOptions()) {
            $route = $this->getRouteWithAllowHeader($collection, $this->response::CODE_OK);
        } elseif ($this->isAutoMethods()) {
            $route = $this->getRouteWithAllowHeader(
                $collection,
                $this->response::CODE_METHOD_NOT_ALLOWED
            );
        }
        if ( ! isset($route)) {
            // @phpstan-ignore-next-line
            $route = $collection->getRouteNotFound() ?? $this->getDefaultRouteNotFound();
        }
        return $route;
    }

    protected function matchCollection(string $origin) : ?RouteCollection
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
                $this->setMatchedOriginArguments(\array_values($matches));
                return $collection;
            }
        }
        return null;
    }

    protected function matchRoute(
        string $method,
        RouteCollection $collection,
        string $path
    ) : ?Route {
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
                $this->setMatchedPathArguments(\array_values($matches));
                $route->setActionArguments($this->getMatchedPathArguments());
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
     * @return static
     */
    public function setAutoOptions(bool $enabled = true) : static
    {
        $this->autoOptions = $enabled;
        return $this;
    }

    #[Pure]
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
     * @return static
     */
    public function setAutoMethods(bool $enabled = true) : static
    {
        $this->autoMethods = $enabled;
        return $this;
    }

    #[Pure]
    public function isAutoMethods() : bool
    {
        return $this->autoMethods;
    }

    protected function getRouteWithAllowHeader(RouteCollection $collection, int $code) : ?Route
    {
        $allowed = $this->getAllowedMethods($collection);
        $response = $this->response;
        return empty($allowed)
            ? null
            : (new Route(
                $this,
                $this->getMatchedOrigin(),
                $this->getMatchedPath(),
                static function () use ($allowed, $code, $response) : void {
                    $response->setStatusLine($code);
                    $response->setHeader('Allow', \implode(', ', $allowed));
                }
            ))->setName('auto-allow-' . $code);
    }

    /**
     * @param RouteCollection $collection
     *
     * @return array<int,string>
     */
    protected function getAllowedMethods(RouteCollection $collection) : array
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
    #[Pure]
    public function hasNamedRoute(
        string $name
    ) : bool {
        foreach ($this->getCollections() as $collection) {
            foreach ($collection->routes as $routes) {
                foreach ($routes as $route) {
                    if ($route->getName() === $name) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @return array<string,Route[]> The HTTP Methods as keys and its Routes as
     * values
     */
    #[Pure]
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
