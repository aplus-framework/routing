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

use BadMethodCallException;
use Closure;
use Error;
use Framework\HTTP\Method;
use InvalidArgumentException;
use LogicException;

/**
 * Class RouteCollection.
 *
 * @property-read string|null $name
 * @property-read string $origin
 * @property-read Router $router
 * @property-read array<string, Route[]> $routes
 *
 * @package routing
 */
class RouteCollection implements \Countable, \JsonSerializable
{
    protected Router $router;
    protected string $origin;
    protected ?string $name;
    /**
     * Array of HTTP Methods as keys and array of Routes as values.
     *
     * @var array<string, Route[]>
     */
    protected array $routes = [];
    /**
     * The Error 404 page action.
     */
    protected Closure | string $notFoundAction;

    /**
     * RouteCollection constructor.
     *
     * @param Router $router A Router instance
     * @param string $origin URL Origin. A string in the following format:
     * `{scheme}://{hostname}[:{port}]`
     * @param string|null $name The collection name
     */
    public function __construct(Router $router, string $origin, string $name = null)
    {
        $this->router = $router;
        $this->setOrigin($origin);
        $this->name = $name;
    }

    /**
     * @param string $method
     * @param array<int,mixed> $arguments
     *
     * @throws BadMethodCallException for method not allowed or method not found
     *
     * @return Route|null
     */
    public function __call(string $method, array $arguments)
    {
        if ($method === 'getRouteNotFound') {
            return $this->getRouteNotFound();
        }
        $class = static::class;
        if (\method_exists($this, $method)) {
            throw new BadMethodCallException(
                "Method not allowed: {$class}::{$method}"
            );
        }
        throw new BadMethodCallException("Method not found: {$class}::{$method}");
    }

    /**
     * @param string $property
     *
     * @throws Error if cannot access property
     *
     * @return mixed
     */
    public function __get(string $property) : mixed
    {
        if ($property === 'name') {
            return $this->name;
        }
        if ($property === 'notFoundAction') {
            return $this->notFoundAction;
        }
        if ($property === 'origin') {
            return $this->origin;
        }
        if ($property === 'router') {
            return $this->router;
        }
        if ($property === 'routes') {
            return $this->routes;
        }
        throw new Error(
            'Cannot access property ' . static::class . '::$' . $property
        );
    }

    public function __isset(string $property) : bool
    {
        return isset($this->{$property});
    }

    /**
     * @param string $origin
     *
     * @return static
     */
    protected function setOrigin(string $origin) : static
    {
        $this->origin = \ltrim($origin, '/');
        return $this;
    }

    /**
     * Get a Route name.
     *
     * @param string $name The current Route name
     *
     * @return string The Route name prefixed with the collection name and a
     * dot if it is set
     */
    protected function getRouteName(string $name) : string
    {
        if (isset($this->name)) {
            $name = $this->name . '.' . $name;
        }
        return $name;
    }

    /**
     * @param string $httpMethod
     * @param Route $route
     *
     * @throws InvalidArgumentException for invalid method
     *
     * @return static
     */
    protected function addRoute(string $httpMethod, Route $route) : static
    {
        $method = \strtoupper($httpMethod);
        if ( ! \in_array($method, [
            'DELETE',
            'GET',
            'OPTIONS',
            'PATCH',
            'POST',
            'PUT',
        ], true)) {
            throw new InvalidArgumentException('Invalid method: ' . $httpMethod);
        }
        $this->routes[$method][] = $route;
        return $this;
    }

    /**
     * Sets the Route Not Found action for this collection.
     *
     * @param Closure|string $action the Route function to run when no Route
     * path is found for this collection
     */
    public function notFound(Closure | string $action) : void
    {
        $this->notFoundAction = $action;
    }

    /**
     * Gets the Route Not Found for this collection.
     *
     * @see RouteCollection::notFound()
     *
     * @return Route|null The Route containing the Not Found Action or null if
     * the Action was not set
     */
    protected function getRouteNotFound() : ?Route
    {
        if (isset($this->notFoundAction)) {
            $this->router->getResponse()->setStatus(404);
            return (new Route(
                $this->router,
                $this->router->getMatchedOrigin(),
                $this->router->getMatchedPath(),
                $this->notFoundAction
            ))->setName(
                $this->getRouteName('collection-not-found')
            );
        }
        return null;
    }

    /**
     * Adds a Route to match many HTTP Methods.
     *
     * @param array<int,string> $httpMethods The HTTP Methods
     * @param string $path The URL path
     * @param array<int,string>|Closure|string $action The Route action
     * @param string|null $name The Route name
     *
     * @see Method::DELETE
     * @see Method::GET
     * @see Method::OPTIONS
     * @see Method::PATCH
     * @see Method::POST
     * @see Method::PUT
     *
     * @return Route
     */
    public function add(
        array $httpMethods,
        string $path,
        array | Closure | string $action,
        string $name = null
    ) : Route {
        $route = $this->makeRoute($path, $action, $name);
        foreach ($httpMethods as $method) {
            $this->addRoute($method, $route);
        }
        return $route;
    }

    /**
     * @param string $path
     * @param array<int,string>|Closure|string $action
     * @param string|null $name
     *
     * @return Route
     */
    protected function makeRoute(
        string $path,
        array | Closure | string $action,
        string $name = null
    ) : Route {
        if (\is_array($action)) {
            $action = $this->makeRouteActionFromArray($action);
        }
        $route = new Route($this->router, $this->origin, $path, $action);
        if ($name !== null) {
            $route->setName($this->getRouteName($name));
        }
        return $route;
    }

    /**
     * @param string $method
     * @param string $path
     * @param array<int,string>|Closure|string $action
     * @param string|null $name
     *
     * @return Route
     */
    protected function addSimple(
        string $method,
        string $path,
        array | Closure | string $action,
        string $name = null
    ) : Route {
        return $this->routes[$method][] = $this->makeRoute($path, $action, $name);
    }

    /**
     * @param array<int,string> $action
     *
     * @return string
     */
    protected function makeRouteActionFromArray(array $action) : string
    {
        if (empty($action[0])) {
            throw new LogicException(
                'When adding a route action as array, the index 0 must be a FQCN'
            );
        }
        if ( ! isset($action[1])) {
            $action[1] = $this->router->getDefaultRouteActionMethod();
        }
        if ( ! isset($action[2])) {
            $action[2] = '*';
        }
        if ($action[2] !== '') {
            $action[2] = '/' . $action[2];
        }
        return $action[0] . '::' . $action[1] . $action[2];
    }

    /**
     * Adds a Route to match the HTTP GET Method.
     *
     * @param string $path The URL path
     * @param array<int,string>|Closure|string $action The Route action
     * @param string|null $name The Route name
     *
     * @see Method::GET
     *
     * @return Route The Route added to the collection
     */
    public function get(
        string $path,
        array | Closure | string $action,
        string $name = null
    ) : Route {
        return $this->addSimple('GET', $path, $action, $name);
    }

    /**
     * Adds a Route to match the HTTP POST Method.
     *
     * @param string $path The URL path
     * @param array<int,string>|Closure|string $action The Route action
     * @param string|null $name The Route name
     *
     * @see Method::POST
     *
     * @return Route The Route added to the collection
     */
    public function post(
        string $path,
        array | Closure | string $action,
        string $name = null
    ) : Route {
        return $this->addSimple('POST', $path, $action, $name);
    }

    /**
     * Adds a Route to match the HTTP PUT Method.
     *
     * @param string $path The URL path
     * @param array<int,string>|Closure|string $action The Route action
     * @param string|null $name The Route name
     *
     * @see Method::PUT
     *
     * @return Route The Route added to the collection
     */
    public function put(
        string $path,
        array | Closure | string $action,
        string $name = null
    ) : Route {
        return $this->addSimple('PUT', $path, $action, $name);
    }

    /**
     * Adds a Route to match the HTTP PATCH Method.
     *
     * @param string $path The URL path
     * @param array<int,string>|Closure|string $action The Route action
     * @param string|null $name The Route name
     *
     * @see Method::PATCH
     *
     * @return Route The Route added to the collection
     */
    public function patch(
        string $path,
        array | Closure | string $action,
        string $name = null
    ) : Route {
        return $this->addSimple('PATCH', $path, $action, $name);
    }

    /**
     * Adds a Route to match the HTTP DELETE Method.
     *
     * @param string $path The URL path
     * @param array<int,string>|Closure|string $action The Route action
     * @param string|null $name The Route name
     *
     * @see Method::DELETE
     *
     * @return Route The Route added to the collection
     */
    public function delete(
        string $path,
        array | Closure | string $action,
        string $name = null
    ) : Route {
        return $this->addSimple('DELETE', $path, $action, $name);
    }

    /**
     * Adds a Route to match the HTTP OPTIONS Method.
     *
     * @param string $path The URL path
     * @param array<int,string>|Closure|string $action The Route action
     * @param string|null $name The Route name
     *
     * @see Method::OPTIONS
     *
     * @return Route The Route added to the collection
     */
    public function options(
        string $path,
        array | Closure | string $action,
        string $name = null
    ) : Route {
        return $this->addSimple('OPTIONS', $path, $action, $name);
    }

    /**
     * Adds a GET Route to match a path and automatically redirects to a URL.
     *
     * @param string $path The URL path
     * @param string $location The URL to redirect
     * @param int|null $code The status code of the response
     *
     * @return Route The Route added to the collection
     */
    public function redirect(string $path, string $location, int $code = null) : Route
    {
        $response = $this->router->getResponse();
        return $this->addSimple(
            'GET',
            $path,
            static function () use ($response, $location, $code) : void {
                $response->redirect($location, [], $code);
            }
        );
    }

    /**
     * Groups many Routes into a URL path.
     *
     * @param string $basePath The URL path to group in
     * @param array<array<mixed|Route>|Route> $routes The Routes to be grouped
     * @param array<string,mixed> $options Custom options passed to the Routes
     *
     * @return array<array<mixed|Route>|Route> The same $routes with updated paths and options
     */
    public function group(string $basePath, array $routes, array $options = []) : array
    {
        $basePath = \rtrim($basePath, '/');
        foreach ($routes as $route) {
            if (\is_array($route)) {
                $this->group($basePath, $route, $options);
                continue;
            }
            $route->setPath($basePath . $route->getPath());
            if ($options) {
                $specificOptions = $options;
                if ($route->getOptions()) {
                    $specificOptions = \array_replace_recursive($options, $route->getOptions());
                }
                $route->setOptions($specificOptions);
            }
        }
        return $routes;
    }

    /**
     * Updates Routes actions, which are strings, prepending a namespace.
     *
     * @param string $namespace The namespace
     * @param array<array<mixed|Route>|Route> $routes The Routes
     *
     * @return array<array<mixed|Route>|Route> The same $routes with updated actions
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
     * @param string $path The URL path
     * @param string $class The name of the class where the resource will point
     * @param string $baseName The base name used as a Route name prefix
     * @param array<int,string> $except Actions not added. Allowed values are:
     * index, create, show, update, replace and delete
     * @param string $placeholder The placeholder. Normally it matches an id, a number
     *
     * @see ResourceInterface
     * @see Router::$placeholders
     *
     * @return array<int,Route> The Routes added to the collection
     */
    public function resource(
        string $path,
        string $class,
        string $baseName,
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
                $class . 'index/*',
                $baseName . '.index'
            );
        }
        if ( ! isset($except['create'])) {
            $routes[] = $this->post(
                $path,
                $class . 'create/*',
                $baseName . '.create'
            );
        }
        if ( ! isset($except['show'])) {
            $routes[] = $this->get(
                $path . $placeholder,
                $class . 'show/*',
                $baseName . '.show'
            );
        }
        if ( ! isset($except['update'])) {
            $routes[] = $this->patch(
                $path . $placeholder,
                $class . 'update/*',
                $baseName . '.update'
            );
        }
        if ( ! isset($except['replace'])) {
            $routes[] = $this->put(
                $path . $placeholder,
                $class . 'replace/*',
                $baseName . '.replace'
            );
        }
        if ( ! isset($except['delete'])) {
            $routes[] = $this->delete(
                $path . $placeholder,
                $class . 'delete/*',
                $baseName . '.delete'
            );
        }
        return $routes;
    }

    /**
     * Adds many Routes that can be used by a User Interface.
     *
     * @param string $path The URL path
     * @param string $class The name of the class where the resource will point
     * @param string $baseName The base name used as a Route name prefix
     * @param array<int,string> $except Actions not added. Allowed values are:
     * index, new, create, show, edit, update, remove and delete
     * @param string $placeholder The placeholder. Normally it matches an id, a number
     *
     * @see PresenterInterface
     * @see Router::$placeholders
     *
     * @return array<int,Route> The Routes added to the collection
     */
    public function presenter(
        string $path,
        string $class,
        string $baseName,
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
                $class . 'index/*',
                $baseName . '.index'
            );
        }
        if ( ! isset($except['new'])) {
            $routes[] = $this->get(
                $path . 'new',
                $class . 'new/*',
                $baseName . '.new'
            );
        }
        if ( ! isset($except['create'])) {
            $routes[] = $this->post(
                $path,
                $class . 'create/*',
                $baseName . '.create'
            );
        }
        if ( ! isset($except['show'])) {
            $routes[] = $this->get(
                $path . $placeholder,
                $class . 'show/*',
                $baseName . '.show'
            );
        }
        if ( ! isset($except['edit'])) {
            $routes[] = $this->get(
                $path . $placeholder . '/edit',
                $class . 'edit/*',
                $baseName . '.edit'
            );
        }
        if ( ! isset($except['update'])) {
            $routes[] = $this->post(
                $path . $placeholder . '/update',
                $class . 'update/*',
                $baseName . '.update'
            );
        }
        if ( ! isset($except['remove'])) {
            $routes[] = $this->get(
                $path . $placeholder . '/remove',
                $class . 'remove/*',
                $baseName . '.remove'
            );
        }
        if ( ! isset($except['delete'])) {
            $routes[] = $this->post(
                $path . $placeholder . '/delete',
                $class . 'delete/*',
                $baseName . '.delete'
            );
        }
        return $routes;
    }

    /**
     * Count routes in the collection.
     *
     * @return int
     */
    public function count() : int
    {
        $count = isset($this->notFoundAction) ? 1 : 0;
        foreach ($this->routes as $routes) {
            $count += \count($routes);
        }
        return $count;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize() : array
    {
        return [
            'origin' => $this->origin,
            'routes' => $this->routes,
            'hasNotFound' => isset($this->notFoundAction),
        ];
    }
}
