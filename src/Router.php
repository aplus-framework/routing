<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework Routing Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\Routing;

use Closure;
use Framework\HTTP\Method;
use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\HTTP\ResponseHeader;
use Framework\HTTP\Status;
use Framework\Language\Language;
use Framework\Routing\Debug\RoutingCollector;
use InvalidArgumentException;
use JetBrains\PhpStorm\Pure;
use OutOfBoundsException;
use RuntimeException;

/**
 * Class Router.
 *
 * @package routing
 */
class Router implements \JsonSerializable
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
    protected ?RouteCollection $matchedCollection = null;
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
    protected RoutingCollector $debugCollector;

    /**
     * Router constructor.
     *
     * @param Response $response
     * @param Language|null $language
     */
    public function __construct(Response $response, Language $language = null)
    {
        $this->response = $response;
        if ($language) {
            $this->setLanguage($language);
        }
    }

    public function __get(string $property) : mixed
    {
        if (\property_exists($this, $property)) {
            return $this->{$property} ?? null;
        }
        throw new OutOfBoundsException(
            'Property not exists: ' . static::class . '::$' . $property
        );
    }

    /**
     * Gets the HTTP Response instance.
     *
     * @return Response
     */
    #[Pure]
    public function getResponse() : Response
    {
        return $this->response;
    }

    public function setLanguage(Language $language = null) : static
    {
        $this->language = $language ?? new Language();
        $this->language->addDirectory(__DIR__ . '/Languages');
        return $this;
    }

    public function getLanguage() : Language
    {
        if ( ! isset($this->language)) {
            $this->setLanguage();
        }
        return $this->language;
    }

    /**
     * Gets the default route action method.
     *
     * Normally, it is "index".
     *
     * @see Router::setDefaultRouteActionMethod()
     *
     * @return string
     */
    #[Pure]
    public function getDefaultRouteActionMethod() : string
    {
        return $this->defaultRouteActionMethod;
    }

    /**
     * Set the class method name to be called when a Route action is set without
     * a method.
     *
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
                $this->response->setStatus(Status::NOT_FOUND);
                if ($this->response->getRequest()->isJson()) {
                    return $this->response->setJson([
                        'status' => [
                            'code' => Status::NOT_FOUND,
                            'reason' => Status::getReason(Status::NOT_FOUND),
                        ],
                    ]);
                }
                $language = $this->getLanguage();
                $lang = $language->getCurrentLocale();
                $dir = $language->getCurrentLocaleDirection();
                $title = $language->render('routing', 'error404');
                $message = $language->render('routing', 'pageNotFound');
                return $this->response->setBody(
                    <<<HTML
                        <!doctype html>
                        <html lang="{$lang}" dir="{$dir}">
                        <head>
                            <meta charset="utf-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1">
                            <title>{$title}</title>
                            <style>
                                body {
                                    background: #fff;
                                    color: #000;
                                    font-family: Arial, Helvetica, sans-serif;
                                    font-size: 1.2rem;
                                    line-height: 1.5rem;
                                    margin: 1rem;
                                }
                            </style>
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
     * Sets the Default Route Not Found action.
     *
     * @param Closure|string $action the function to run when no Route path is found
     *
     * @return static
     */
    public function setDefaultRouteNotFound(Closure | string $action) : static
    {
        $this->defaultRouteNotFound = $action;
        return $this;
    }

    /**
     * Gets the Route Not Found.
     *
     * Must be called after {@see Router::match()} and will return the Route
     * Not Found from the matched collection or the Default Route Not Found
     * from the router.
     *
     * @see RouteCollection::notFound()
     * @see Router::setDefaultRouteNotFound()
     *
     * @return Route
     */
    public function getRouteNotFound() : Route
    {
        // @phpstan-ignore-next-line
        return $this->getMatchedCollection()?->getRouteNotFound()
            ?? $this->getDefaultRouteNotFound();
    }

    /**
     * Adds Router placeholders.
     *
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
     * Gets all Router placeholders.
     *
     * @return array<string,string>
     */
    #[Pure]
    public function getPlaceholders() : array
    {
        return static::$placeholders;
    }

    /**
     * Replaces string placeholders with patterns or patterns with placeholders.
     *
     * @param string $string The string with placeholders or patterns
     * @param bool $flip Set true to replace patterns with placeholders
     *
     * @return string
     */
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
     * Fills argument values into a string with placeholders.
     *
     * @param string $string The input string
     * @param string ...$arguments Values to fill the string placeholders
     *
     * @throws InvalidArgumentException if param not required, empty or invalid
     * @throws RuntimeException if a pattern position is not found
     *
     * @return string The string with argument values in place of placeholders
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
            $string = \substr_replace(
                $string,
                $arguments[$index],
                \strpos($string, $pattern), // @phpstan-ignore-line
                \strlen($pattern)
            );
        }
        return $string;
    }

    /**
     * Serves a RouteCollection to a specific Origin.
     *
     * @param string|null $origin URL Origin. A string in the following format:
     * `{scheme}://{hostname}[:{port}]`. Null to auto-detect.
     * @param callable $callable A function receiving an instance of RouteCollection
     * as the first parameter
     * @param string|null $collectionName The RouteCollection name
     *
     * @return static
     */
    public function serve(?string $origin, callable $callable, string $collectionName = null) : static
    {
        if (isset($this->debugCollector)) {
            $start = \microtime(true);
            $this->addServedCollection($origin, $callable, $collectionName);
            $end = \microtime(true);
            $this->debugCollector->addData([
                'type' => 'serve',
                'start' => $start,
                'end' => $end,
                'collectionId' => \spl_object_id(
                    $this->collections[\array_key_last($this->collections)]
                ),
            ]);
            return $this;
        }
        return $this->addServedCollection($origin, $callable, $collectionName);
    }

    protected function addServedCollection(
        ?string $origin,
        callable $callable,
        string $collectionName = null
    ) : static {
        if ($origin === null) {
            $origin = $this->response->getRequest()->getUrl()->getOrigin();
        }
        $collection = new RouteCollection($this, $origin, $collectionName);
        $callable($collection);
        $this->addCollection($collection);
        return $this;
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
     * Gets all Route Collections.
     *
     * @return array<int,RouteCollection>
     */
    #[Pure]
    public function getCollections() : array
    {
        return $this->collections;
    }

    /**
     * Gets the matched Route Collection.
     *
     * Note: Will return null if no URL Origin was matched in a Route Collection
     *
     * @return RouteCollection|null
     */
    #[Pure]
    public function getMatchedCollection() : ?RouteCollection
    {
        return $this->matchedCollection;
    }

    protected function setMatchedCollection(RouteCollection $matchedCollection) : static
    {
        $this->matchedCollection = $matchedCollection;
        return $this;
    }

    /**
     * Gets the matched Route.
     *
     * @return Route|null
     */
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

    /**
     * Gets the matched URL Path.
     *
     * @return string|null
     */
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
     * Gets the matched URL Path arguments.
     *
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

    /**
     * Gets the matched URL.
     *
     * Note: This method does not return the URL query. If it is needed, get
     * with {@see Request::getUrl()}.
     *
     * @return string|null
     */
    #[Pure]
    public function getMatchedUrl() : ?string
    {
        return $this->getMatchedOrigin()
            ? $this->getMatchedOrigin() . $this->getMatchedPath()
            : null;
    }

    /**
     * Gets the matched URL Origin.
     *
     * @return string|null
     */
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
     * Gets the matched URL Origin arguments.
     *
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
     * @see Router::serve()
     *
     * @return Route Always returns a Route, even if it is the Route Not Found
     */
    public function match() : Route
    {
        if (isset($this->debugCollector)) {
            $start = \microtime(true);
            $route = $this->makeMatchedRoute();
            $end = \microtime(true);
            $this->debugCollector->addData([
                'type' => 'match',
                'start' => $start,
                'end' => $end,
            ]);
            return $route;
        }
        return $this->makeMatchedRoute();
    }

    protected function makeMatchedRoute() : Route
    {
        $method = $this->response->getRequest()->getMethod();
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        $url = $this->response->getRequest()->getUrl();
        $this->setMatchedPath($url->getPath());
        $this->setMatchedOrigin($url->getOrigin());
        $this->matchedCollection = $this->matchCollection($url->getOrigin());
        if ( ! $this->matchedCollection) {
            return $this->matchedRoute = $this->getDefaultRouteNotFound();
        }
        return $this->matchedRoute = $this->matchRoute($method, $this->matchedCollection, $url->getPath())
            ?? $this->getAlternativeRoute($method, $this->matchedCollection);
    }

    protected function getAlternativeRoute(string $method, RouteCollection $collection) : Route
    {
        if ($method === 'OPTIONS' && $this->isAutoOptions()) {
            $route = $this->getRouteWithAllowHeader($collection, Status::OK);
        } elseif ($this->isAutoMethods()) {
            $route = $this->getRouteWithAllowHeader(
                $collection,
                Status::METHOD_NOT_ALLOWED
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
        foreach ($routes[$method] as $route) {
            $pattern = $this->replacePlaceholders($route->getPath());
            $matched = \preg_match(
                '#^' . $pattern . '$#',
                $path,
                $matches
            );
            if ($matched) {
                unset($matches[0]);
                $this->setMatchedPathArguments(\array_values($matches));
                $route->setActionArguments($this->getMatchedPathArguments());
                return $route;
            }
        }
        return null;
    }

    /**
     * Enable/disable the feature of auto-detect and show HTTP allowed methods
     * via the Allow header when the Request has the OPTIONS method.
     *
     * @param bool $enabled true to enable, false to disable
     *
     * @see Method::OPTIONS
     * @see ResponseHeader::ALLOW
     *
     * @return static
     */
    public function setAutoOptions(bool $enabled = true) : static
    {
        $this->autoOptions = $enabled;
        return $this;
    }

    /**
     * Tells if auto options is enabled.
     *
     * @see Router::setAutoOptions()
     *
     * @return bool
     */
    #[Pure]
    public function isAutoOptions() : bool
    {
        return $this->autoOptions;
    }

    /**
     * Enable/disable the feature of auto-detect and show HTTP allowed methods
     * via the Allow header when a route with the requested method does not exist.
     *
     * A response with code 405 "Method Not Allowed" will trigger.
     *
     * @param bool $enabled true to enable, false to disable
     *
     * @see Status::METHOD_NOT_ALLOWED
     * @see ResponseHeader::ALLOW
     *
     * @return static
     */
    public function setAutoMethods(bool $enabled = true) : static
    {
        $this->autoMethods = $enabled;
        return $this;
    }

    /**
     * Tells if auto methods is enabled.
     *
     * @see Router::setAutoMethods()
     *
     * @return bool
     */
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
                    $response->setStatus($code);
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
     * Tells if it has a named route.
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
     * Gets all routes, except the not found.
     *
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

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize() : array
    {
        return [
            'matched' => $this->getMatchedRoute(),
            'collections' => $this->getCollections(),
            'isAutoMethods' => $this->isAutoMethods(),
            'isAutoOptions' => $this->isAutoOptions(),
            'placeholders' => $this->getPlaceholders(),
        ];
    }

    public function setDebugCollector(RoutingCollector $debugCollector) : static
    {
        $this->debugCollector = $debugCollector;
        $this->debugCollector->setRouter($this);
        return $this;
    }

    public function getDebugCollector() : ?RoutingCollector
    {
        return $this->debugCollector ?? null;
    }
}
