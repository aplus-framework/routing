<?php
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
use Framework\HTTP\Response;
use InvalidArgumentException;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use JsonException;

/**
 * Class Route.
 *
 * @package routing
 */
class Route implements \JsonSerializable
{
    protected Router $router;
    protected string $origin;
    protected string $path;
    protected Closure | string $action;
    /**
     * @var array<int,string>
     */
    protected array $actionArguments = [];
    protected ?string $name = null;
    /**
     * @var array<string,mixed>
     */
    protected array $options = [];

    /**
     * Route constructor.
     *
     * @param Router $router A Router instance
     * @param string $origin URL Origin. A string in the following format:
     * {scheme}://{hostname}[:{port}]
     * @param string $path URL Path. A string starting with '/'
     * @param Closure|string $action The action
     */
    public function __construct(
        Router $router,
        string $origin,
        string $path,
        Closure | string $action
    ) {
        $this->router = $router;
        $this->setOrigin($origin);
        $this->setPath($path);
        $this->setAction($action);
    }

    /**
     * Gets the Route URL origin.
     *
     * @param string ...$arguments Arguments to fill the URL Origin placeholders
     *
     * @return string
     */
    public function getOrigin(string ...$arguments) : string
    {
        if ($arguments) {
            return $this->router->fillPlaceholders($this->origin, ...$arguments);
        }
        return $this->origin;
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
     * Gets the Route URL.
     *
     * Note: Arguments must be passed if placeholders need to be filled.
     *
     * @param array<mixed> $originArgs Arguments to fill the URL Origin placeholders
     * @param array<mixed> $pathArgs Arguments to fill the URL Path placeholders
     *
     * @return string
     */
    public function getUrl(array $originArgs = [], array $pathArgs = []) : string
    {
        $originArgs = static::toArrayOfStrings($originArgs);
        $pathArgs = static::toArrayOfStrings($pathArgs);
        return $this->getOrigin(...$originArgs) . $this->getPath(...$pathArgs);
    }

    /**
     * Gets Route options.
     *
     * @return array<string,mixed>
     */
    #[Pure]
    public function getOptions() : array
    {
        return $this->options;
    }

    /**
     * Sets options to be used in a specific environment application.
     * For example: its possible set Access Control List options, Locations,
     * Middleware filters, etc.
     *
     * @param array<string,mixed> $options
     *
     * @return static
     */
    public function setOptions(array $options) : static
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Gets the Route name.
     *
     * @return string|null
     */
    #[Pure]
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * Sets the Route name.
     *
     * @param string $name
     *
     * @return static
     */
    public function setName(string $name) : static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Sets the Route URL path.
     *
     * @param string $path
     *
     * @return static
     */
    public function setPath(string $path) : static
    {
        $this->path = '/' . \trim($path, '/');
        return $this;
    }

    /**
     * Gets the Route URL path.
     *
     * @param string ...$arguments Arguments to fill the URL Path placeholders
     *
     * @return string
     */
    public function getPath(string ...$arguments) : string
    {
        if ($arguments) {
            return $this->router->fillPlaceholders($this->path, ...$arguments);
        }
        return $this->path;
    }

    /**
     * Gets the Route action.
     *
     * @return Closure|string
     */
    #[Pure]
    public function getAction() : Closure | string
    {
        return $this->action;
    }

    /**
     * Sets the Route action.
     *
     * @param Closure|string $action A Closure or a string in the format of the
     * `__METHOD__` constant. Example: `App\Blog::show`.
     *
     * The action can be suffixed with ordered parameters, separated by slashes,
     * to set how the arguments will be passed to the class method.
     * Example: `App\Blog::show/0/2/1`.
     *
     * And, also with the asterisk wildcard, to pass all arguments in the
     * incoming order. Example: `App\Blog::show/*`
     *
     * @see Route::setActionArguments()
     * @see Route::run()
     *
     * @return static
     */
    public function setAction(Closure | string $action) : static
    {
        $this->action = \is_string($action) ? \trim($action, '\\') : $action;
        return $this;
    }

    /**
     * Gets the Route action arguments.
     *
     * @return array<int,string>
     */
    #[Pure]
    public function getActionArguments() : array
    {
        return $this->actionArguments;
    }

    /**
     * Sets the Route action arguments.
     *
     * @param array<int,string> $arguments The arguments. Note that the indexes set
     * the order of how the arguments are passed to the Action
     *
     * @see Route::setAction()
     *
     * @return static
     */
    public function setActionArguments(array $arguments) : static
    {
        \ksort($arguments);
        $this->actionArguments = $arguments;
        return $this;
    }

    /**
     * Runs the Route action.
     *
     * @param mixed ...$construct Class constructor arguments
     *
     * @throws JsonException if the action result is an array, or an instance of
     * JsonSerializable, and the Response cannot be set as JSON
     * @throws RoutingException if class is not an instance of {@see RouteActions},
     * action method not exists or if the result of the action method has not
     * a valid type
     *
     * @return Response The Response with the action result appended on the body
     */
    public function run(mixed ...$construct) : Response
    {
        $debug = $this->router->getDebugCollector();
        if ($debug) {
            $start = \microtime(true);
            $addToDebug = static fn () => $debug->addData([
                'type' => 'run',
                'start' => $start,
                'end' => \microtime(true),
            ]);
        }
        $action = $this->getAction();
        if ($action instanceof Closure) {
            $result = $action($this->getActionArguments(), ...$construct);
            $response = $this->makeResponse($result);
            if ($debug) {
                $addToDebug();
            }
            return $response;
        }
        if ( ! \str_contains($action, '::')) {
            $action .= '::' . $this->router->getDefaultRouteActionMethod();
        }
        [$classname, $action] = \explode('::', $action, 2);
        [$method, $arguments] = $this->extractMethodAndArguments($action);
        if ( ! \class_exists($classname)) {
            throw new RoutingException("Class not exists: {$classname}");
        }
        /**
         * @var RouteActions $class
         */
        $class = new $classname(...$construct);
        if ( ! $class instanceof RouteActions) {
            throw new RoutingException(
                'Class ' . $class::class . ' is not an instance of ' . RouteActions::class
            );
        }
        if ( ! \method_exists($class, $method)) {
            throw new RoutingException(
                "Class action method not exists: {$classname}::{$method}"
            );
        }
        $result = $class->beforeAction($method, $arguments); // @phpstan-ignore-line
        $ran = false;
        if ($result === null) {
            $result = $class->{$method}(...$arguments);
            $ran = true;
        }
        $result = $class->afterAction($method, $arguments, $ran, $result); // @phpstan-ignore-line
        $response = $this->makeResponse($result);
        if ($debug) {
            $addToDebug();
        }
        return $response;
    }

    /**
     * Make the final Response used in the 'run' method.
     *
     * @throws JsonException if the $result is an array, or an instance of
     * JsonSerializable, and the Response cannot be set as JSON
     * @throws RoutingException if the $result type is invalid
     */
    protected function makeResponse(mixed $result) : Response
    {
        $result = $this->makeResponseBodyPart($result);
        return $this->router->getResponse()->appendBody($result);
    }

    /**
     * Make a string to be appended in the Response body based in the route
     * action result.
     *
     * @param mixed $result The return value of the matched route action
     *
     * @throws JsonException if the $result is an array, or an instance of
     * JsonSerializable, and the Response cannot be set as JSON
     * @throws RoutingException if the $result type is invalid
     *
     * @return string
     */
    protected function makeResponseBodyPart(mixed $result) : string
    {
        if ($result === null || $result instanceof Response) {
            return '';
        }
        if (\is_scalar($result)) {
            return (string) $result;
        }
        if (\is_object($result) && \method_exists($result, '__toString')) {
            return (string) $result;
        }
        if (
            \is_array($result)
            || $result instanceof \stdClass
            || $result instanceof \JsonSerializable
        ) {
            $this->router->getResponse()->setJson($result);
            return '';
        }
        $type = \get_debug_type($result);
        throw new RoutingException(
            "Invalid action return type '{$type}'" . $this->onNamedRoutePart()
        );
    }

    /**
     * @param string $part An action part like: index/0/2/1
     *
     * @throws InvalidArgumentException for undefined action argument
     *
     * @return array<int,mixed> The action method in the first index, the action
     * arguments in the second
     */
    #[ArrayShape([0 => 'string', 1 => 'array'])]
    protected function extractMethodAndArguments(
        string $part
    ) : array {
        if ( ! \str_contains($part, '/')) {
            return [$part, []];
        }
        $arguments = \explode('/', $part);
        $method = $arguments[0];
        unset($arguments[0]);
        $actionArguments = $this->getActionArguments();
        $arguments = \array_values($arguments);
        foreach ($arguments as $index => $arg) {
            if (\is_numeric($arg)) {
                $arg = (int) $arg;
                if (\array_key_exists($arg, $actionArguments)) {
                    $arguments[$index] = $actionArguments[$arg];
                    continue;
                }
                throw new InvalidArgumentException(
                    "Undefined action argument: {$arg}" . $this->onNamedRoutePart()
                );
            }
            if ($arg !== '*') {
                throw new InvalidArgumentException(
                    'Action argument is not numeric, or has not an allowed wildcard, on index ' . $index
                    . $this->onNamedRoutePart()
                );
            }
            if ($index !== 0 || \count($arguments) > 1) {
                throw new InvalidArgumentException(
                    'Action arguments can only contain an asterisk wildcard and must be passed alone'
                    . $this->onNamedRoutePart()
                );
            }
            $arguments = $actionArguments;
        }
        return [
            $method,
            $arguments,
        ];
    }

    #[Pure]
    protected function onNamedRoutePart() : string
    {
        $routeName = $this->getName();
        $part = $routeName ? "named route '{$routeName}'" : 'unnamed route';
        return ', on ' . $part;
    }

    public function jsonSerialize() : string
    {
        return $this->getUrl();
    }

    /**
     * @param array<mixed> $array
     *
     * @return array<int,string>
     */
    protected static function toArrayOfStrings(array $array) : array
    {
        if ($array === []) {
            return [];
        }
        return \array_map(static function (mixed $value) : string {
            return (string) $value;
        }, $array);
    }
}
