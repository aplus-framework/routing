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
use InvalidArgumentException;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use JsonException;

/**
 * Class Route.
 */
class Route
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
	 *                               {scheme}://{hostname}[:{port}]
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
	 * Gets the URL Origin.
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
	 * Gets the URL.
	 *
	 * @param array<int,string> $originArgs Arguments to fill the URL Origin placeholders
	 * @param array<int,string> $pathArgs Arguments to fill the URL Path placeholders
	 *
	 * @return string
	 */
	public function getURL(array $originArgs = [], array $pathArgs = []) : string
	{
		$originArgs = static::toArrayOfStrings($originArgs);
		$pathArgs = static::toArrayOfStrings($pathArgs);
		return $this->getOrigin(...$originArgs) . $this->getPath(...$pathArgs);
	}

	/**
	 * @return array<string,mixed>
	 */
	#[Pure]
	public function getOptions() : array
	{
		return $this->options;
	}

	/**
	 * @param array<string,mixed> $options
	 *
	 * @return static
	 */
	public function setOptions(array $options) : static
	{
		$this->options = $options;
		return $this;
	}

	#[Pure]
	public function getName() : ?string
	{
		return $this->name;
	}

	/**
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
	 * Gets the URL Path.
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

	#[Pure]
	public function getAction() : Closure | string
	{
		return $this->action;
	}

	/**
	 * Sets the Route Action.
	 *
	 * @param Closure|string $action A \Closure or a string in the format of the
	 * __METHOD__ constant. Example: App\Blog::show/0/2/1. Where /0/2/1 is the
	 * method parameters order
	 *
	 * @see setActionParams
	 * @see run
	 *
	 * @return static
	 */
	public function setAction(Closure | string $action) : static
	{
		$this->action = \is_string($action) ? \trim($action, '\\') : $action;
		return $this;
	}

	/**
	 * @return array<int,string>
	 */
	#[Pure]
	public function getActionArguments() : array
	{
		return $this->actionArguments;
	}

	/**
	 * Sets the Action parameters.
	 *
	 * @param array<int,string> $arguments The arguments. Note that the indexes set
	 * the order of how the arguments are passed to the Action
	 *
	 * @see setAction
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
	 * Run the Route Action.
	 *
	 * @param mixed ...$construct Class constructor parameters
	 *
	 * @throws JsonException if the action result is an array, or an instance of
	 * JsonSerializable, and the Response cannot be set as JSON
	 * @throws RoutingException if class is not an instance of RouteAction,
	 * action method not exists or if the result of the action method has not
	 * a valid type
	 *
	 * @return Response The Response with the action result appended on the body
	 */
	public function run(mixed ...$construct) : Response
	{
		$action = $this->getAction();
		if ($action instanceof Closure) {
			$result = $action($this->getActionArguments(), ...$construct);
			return $this->makeResponse($result);
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
		$run = false;
		if ($result === null) {
			$result = $class->{$method}(...$arguments);
			$run = true;
		}
		$result = $class->afterAction($method, $arguments, $run, $result); // @phpstan-ignore-line
		return $this->makeResponse($result);
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
			$this->router->getResponse()->setJSON($result);
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
		if ($arguments) {
			$actionArguments = $this->getActionArguments();
			$arguments = \array_values($arguments);
			foreach ($arguments as $index => $arg) {
				if ( ! \is_numeric($arg)) {
					throw new InvalidArgumentException(
						'Action argument is not numeric on index ' . $index
						. $this->onNamedRoutePart()
					);
				}
				$arg = (int) $arg;
				if ( ! \array_key_exists($arg, $actionArguments)) {
					throw new InvalidArgumentException(
						"Undefined action argument: {$arg}" . $this->onNamedRoutePart()
					);
				}
				$arguments[$index] = $actionArguments[$arg];
			}
		}
		return [
			$method,
			$arguments,
		];
	}

	#[Pure]
	protected function onNamedRoutePart() : string
	{
		$route_name = $this->getName();
		$part = $route_name ? "named route '{$route_name}'" : 'unnamed route';
		return ', on ' . $part;
	}

	/**
	 * @param array<int,mixed> $array
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
