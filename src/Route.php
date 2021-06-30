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
use InvalidArgumentException;
use JetBrains\PhpStorm\Pure;

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
	protected array $actionParams = [];
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
	 * @param string ...$params Parameters to fill the URL Origin placeholders
	 *
	 * @return string
	 */
	public function getOrigin(string ...$params) : string
	{
		if ($params) {
			return $this->router->fillPlaceholders($this->origin, ...$params);
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
	 * @param array<int,string> $origin_params Parameters to fill the URL Origin placeholders
	 * @param array<int,string> $path_params Parameters to fill the URL Path placeholders
	 *
	 * @return string
	 */
	public function getURL(array $origin_params = [], array $path_params = []) : string
	{
		return $this->getOrigin(...$origin_params) . $this->getPath(...$path_params);
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
	 * @param string ...$params Parameters to fill the URL Path placeholders
	 *
	 * @return string
	 */
	public function getPath(string ...$params) : string
	{
		if ($params) {
			return $this->router->fillPlaceholders($this->path, ...$params);
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
	public function getActionParams() : array
	{
		return $this->actionParams;
	}

	/**
	 * Sets the Action parameters.
	 *
	 * @param array<int,string> $params The parameters. Note that the indexes set
	 * the order of how the parameters are passed to the Action
	 *
	 * @see setAction
	 *
	 * @return static
	 */
	public function setActionParams(array $params) : static
	{
		\ksort($params);
		$this->actionParams = $params;
		return $this;
	}

	/**
	 * Run the Route Action.
	 *
	 * @param mixed ...$construct Class constructor parameters
	 *
	 * @throws Exception if class or method not exists
	 *
	 * @return mixed The action returned value
	 */
	public function run(mixed ...$construct) : mixed
	{
		$action = $this->getAction();
		if ($action instanceof Closure) {
			return $action($this->getActionParams(), ...$construct);
		}
		if ( ! \str_contains($action, '::')) {
			$action .= '::' . $this->router->getDefaultRouteActionMethod();
		}
		[$classname, $action] = \explode('::', $action, 2);
		[$action, $params] = $this->extractActionAndParams($action);
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
		if ( ! \method_exists($class, $action)) {
			throw new RoutingException(
				"Class action method not exists: {$classname}::{$action}"
			);
		}
		$class->actionMethod = $action; // @phpstan-ignore-line
		$class->actionParams = $params; // @phpstan-ignore-line
		$class->actionRun = false; // @phpstan-ignore-line
		$response = $class->beforeAction(); // @phpstan-ignore-line
		if ($response === null) {
			$response = $class->{$action}(...$params);
			$class->actionRun = true; // @phpstan-ignore-line
		}
		return $class->afterAction($response); // @phpstan-ignore-line
	}

	/**
	 * @param string $action An action part like: index/0/2/1
	 *
	 * @throws InvalidArgumentException for undefined action parameter
	 *
	 * @return array<int,mixed>
	 */
	protected function extractActionAndParams(string $action) : array
	{
		if ( ! \str_contains($action, '/')) {
			return [$action, []];
		}
		$params = \explode('/', $action);
		$action = $params[0];
		unset($params[0]);
		if ($params) {
			$action_params = $this->getActionParams();
			$params = \array_values($params);
			foreach ($params as $index => $param) {
				if ( ! \is_numeric($param)) {
					throw new InvalidArgumentException(
						'Action parameter is not numeric on index ' . $index
					);
				}
				$param = (int) $param;
				if ( ! \array_key_exists($param, $action_params)) {
					throw new InvalidArgumentException("Undefined action parameter: {$param}");
				}
				$params[$index] = $action_params[$param];
			}
		}
		return [
			$action,
			$params,
		];
	}
}
