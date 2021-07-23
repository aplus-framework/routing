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

/**
 * Class RouteCollection.
 *
 * @property-read string $origin
 * @property-read Router $router
 * @property-read array<string, Route[]> $routes
 */
class RouteCollection implements \Countable
{
    protected Router $router;
    protected string $origin;
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
     * Collection constructor.
     *
     * @param Router $router A Router instance
     * @param string $origin URL Origin. A string in the following format:
     * {scheme}://{hostname}[:{port}]
     */
    public function __construct(Router $router, string $origin)
    {
        $this->router = $router;
        $this->setOrigin($origin);
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
     * @param string $httpMethod
     * @param Route $route
     *
     * @return static
     */
    protected function addRoute(string $httpMethod, Route $route) : static
    {
        $this->routes[\strtoupper($httpMethod)][] = $route;
        return $this;
    }

    /**
     * Sets the action to the Collection Route Not Found.
     *
     * @param Closure|string $action the Route function to run when no Route
     * path is found for this collection
     */
    public function notFound(Closure | string $action) : void
    {
        $this->notFoundAction = $action;
    }

    /**
     * Gets the Route Not Found for this Collection.
     *
     * @see notFound
     *
     * @return Route|null The Route containing the Not Found Action or null if
     * the Action was not set
     */
    protected function getRouteNotFound() : ?Route
    {
        return ! isset($this->notFoundAction)
            ? null
            : (new Route(
                $this->router,
                $this->router->getMatchedOrigin(),
                $this->router->getMatchedPath(),
                $this->notFoundAction
            ))->setName('collection-not-found');
    }

    /**
     * Adds a Route to match many HTTP Methods.
     *
     * @param array<int,string> $httpMethods The HTTP Methods
     * @param string $path The URL path
     * @param Closure|string $action The Route action
     * @param string|null $name The Route name
     *
     * @return Route
     */
    public function add(
        array $httpMethods,
        string $path,
        Closure | string $action,
        string $name = null
    ) : Route {
        $route = new Route($this->router, $this->origin, $path, $action);
        if ($name) {
            $route->setName($name);
        }
        foreach ($httpMethods as $method) {
            $this->addRoute($method, $route);
        }
        return $route;
    }

    /**
     * Adds a Route to match the HTTP Method GET.
     *
     * @param string $path The URL path
     * @param Closure|string $action The Route action
     * @param string|null $name The Route name
     *
     * @return Route The Route added to the Collection
     */
    public function get(string $path, Closure | string $action, string $name = null) : Route
    {
        return $this->add(['GET'], $path, $action, $name);
    }

    /**
     * Adds a Route to match the HTTP Method POST.
     *
     * @param string $path The URL path
     * @param Closure|string $action The Route action
     * @param string|null $name The Route name
     *
     * @return Route The Route added to the Collection
     */
    public function post(string $path, Closure | string $action, string $name = null) : Route
    {
        return $this->add(['POST'], $path, $action, $name);
    }

    /**
     * Adds a Route to match the HTTP Method PUT.
     *
     * @param string $path The URL path
     * @param Closure|string $action The Route action
     * @param string|null $name The Route name
     *
     * @return Route The Route added to the Collection
     */
    public function put(string $path, Closure | string $action, string $name = null) : Route
    {
        return $this->add(['PUT'], $path, $action, $name);
    }

    /**
     * Adds a Route to match the HTTP Method PATCH.
     *
     * @param string $path The URL path
     * @param Closure|string $action The Route action
     * @param string|null $name The Route name
     *
     * @return Route The Route added to the Collection
     */
    public function patch(string $path, Closure | string $action, string $name = null) : Route
    {
        return $this->add(['PATCH'], $path, $action, $name);
    }

    /**
     * Adds a Route to match the HTTP Method DELETE.
     *
     * @param string $path The URL path
     * @param Closure|string $action The Route action
     * @param string|null $name The Route name
     *
     * @return Route The Route added to the Collection
     */
    public function delete(string $path, Closure | string $action, string $name = null) : Route
    {
        return $this->add(['DELETE'], $path, $action, $name);
    }

    /**
     * Adds a Route to match the HTTP Method OPTIONS.
     *
     * @param string $path The URL path
     * @param Closure|string $action The Route action
     * @param string|null $name The Route name
     *
     * @return Route The Route added to the Collection
     */
    public function options(string $path, Closure | string $action, string $name = null) : Route
    {
        return $this->add(['OPTIONS'], $path, $action, $name);
    }

    /**
     * Adds a GET Route to match a path and automatically redirects to a URL.
     *
     * @param string $path The URL path
     * @param string $location The URL to redirect
     * @param int|null $code The status code of the response
     *
     * @return Route The Route added to the Collection
     */
    public function redirect(string $path, string $location, int $code = null) : Route
    {
        $response = $this->router->getResponse();
        return $this->add(['GET'], $path, static function () use ($response, $location, $code) : void {
            $response->redirect($location, [], $code);
        });
    }

    /**
     * Groups many Routes into a URL path.
     *
     * @param string $basePath The URL path to group in
     * @param array<int,array|Route> $routes The Routes to be grouped
     * @param array<string,mixed> $options Custom options passed to the Routes
     *
     * @return array<int,array|Route> The same $routes with updated paths and options
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
     * @param string $namespace The namespace
     * @param array<int,array|Route> $routes The Routes
     *
     * @return array<int,array|Route> The same $routes with updated actions
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
     * @return array<int,Route> The Routes added to the Collection
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
     * @return array<int,Route> The Routes added to the Collection
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
     * Count routes in the Collection.
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
}
