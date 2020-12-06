<?php namespace Framework\Routing;

use Closure;
use InvalidArgumentException;

/**
 * Class Route.
 */
class Route
{
	/**
	 * @var Router
	 */
	protected Router $router;
	protected string $origin;
	protected string $path;
	/**
	 * @var Closure|string
	 */
	protected $action;
	/**
	 * @var array|string[]
	 */
	protected array $actionParams = [];
	protected ?string $name = null;
	/**
	 * @var array|mixed[]
	 */
	protected array $options = [];

	/**
	 * Route constructor.
	 *
	 * @param Router                  $router A Router instance
	 * @param string                  $origin URL Origin. A string in the following format:
	 *                                        {scheme}://{hostname}[:{port}]
	 * @param string                  $path   URL Path. A string starting with '/'
	 * @param callable|Closure|string $action The action
	 */
	public function __construct(Router $router, string $origin, string $path, $action)
	{
		$this->router = $router;
		$this->setOrigin($origin);
		$this->setPath($path);
		$this->setAction($action);
	}

	/**
	 * Gets the URL Origin.
	 *
	 * @param mixed $params Parameters to fill the URL Origin placeholders
	 *
	 * @return string
	 */
	public function getOrigin(...$params) : string
	{
		if ($params) {
			return $this->router->fillPlaceholders($this->origin, ...$params);
		}
		return $this->origin;
	}

	/**
	 * @param string $origin
	 *
	 * @return $this
	 */
	protected function setOrigin(string $origin)
	{
		$this->origin = \ltrim($origin, '/');
		return $this;
	}

	/**
	 * Gets the URL.
	 *
	 * @param array|string[] $origin_params Parameters to fill the URL Origin placeholders
	 * @param array|string[] $path_params   Parameters to fill the URL Path placeholders
	 *
	 * @return string
	 */
	public function getURL(array $origin_params = [], array $path_params = []) : string
	{
		return $this->getOrigin(...$origin_params) . $this->getPath(...$path_params);
	}

	/**
	 * @return array|mixed[]
	 */
	public function getOptions() : array
	{
		return $this->options;
	}

	/**
	 * @param array|mixed[] $options
	 *
	 * @return $this
	 */
	public function setOptions(array $options)
	{
		$this->options = $options;
		return $this;
	}

	public function getName() : ?string
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 *
	 * @return $this
	 */
	public function setName(string $name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @param string $path
	 *
	 * @return $this
	 */
	public function setPath(string $path)
	{
		$this->path = '/' . \trim($path, '/');
		return $this;
	}

	/**
	 * Gets the URL Path.
	 *
	 * @param mixed ...$params Parameters to fill the URL Path placeholders
	 *
	 * @return string
	 */
	public function getPath(...$params) : string
	{
		if ($params) {
			return $this->router->fillPlaceholders($this->path, ...$params);
		}
		return $this->path;
	}

	/**
	 * @return Closure|string
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * Sets the Route Action.
	 *
	 * @param callable|Closure|string $action A \Closure or a string in the format of the
	 *                                        __METHOD__
	 *                                        constant. Example: App\Blog::show/0/2/1. Where /0/2/1
	 *                                        is the method parameters order
	 *
	 * @see setActionParams
	 * @see run
	 *
	 * @return $this
	 */
	public function setAction($action)
	{
		$this->action = \is_string($action) ? \trim($action, '\\') : $action;
		return $this;
	}

	/**
	 * @return array|string[]
	 */
	public function getActionParams() : array
	{
		return $this->actionParams;
	}

	/**
	 * Sets the Action parameters.
	 *
	 * @param array|string[] $params The parameters. Note that the indexes set the order of how the
	 *                               parameters are passed to the Action
	 *
	 * @see setAction
	 *
	 * @return $this
	 */
	public function setActionParams(array $params)
	{
		\ksort($params);
		$this->actionParams = $params;
		return $this;
	}

	/**
	 * Run the Route Action.
	 *
	 * @param mixed $construct Class constructor parameters
	 *
	 * @throws Exception if class or method not exists
	 *
	 * @return mixed The action returned value
	 */
	public function run(...$construct)
	{
		$action = $this->getAction();
		if ($action instanceof Closure) {
			return $action($this->getActionParams(), ...$construct);
		}
		if (\strpos($action, '::') === false) {
			$action .= '::' . $this->router->getDefaultRouteActionMethod();
		}
		[$classname, $action] = \explode('::', $action, 2);
		[$action, $params] = $this->extractActionAndParams($action);
		if ( ! \class_exists($classname)) {
			throw new Exception("Class not exists: {$classname}");
		}
		$class = new $classname(...$construct);
		if ( ! \method_exists($class, $action)) {
			throw new Exception(
				"Class method not exists: {$classname}::{$action}"
			);
		}
		if (\method_exists($class, 'beforeAction')) {
			$response = $class->beforeAction($action, $params);
			if ($response !== null) {
				return $response;
			}
		}
		$response = $class->{$action}(...$params);
		if ($response === null && \method_exists($class, 'afterAction')) {
			$response = $class->afterAction($action, $params);
		}
		return $response;
	}

	/**
	 * @param string $action An action part like: index/0/2/1
	 *
	 * @throws InvalidArgumentException for undefined action parameter
	 *
	 * @return array|mixed[]
	 */
	protected function extractActionAndParams(string $action) : array
	{
		if (\strpos($action, '/') === false) {
			return [$action, []];
		}
		$params = \explode('/', $action);
		$action = $params[0];
		unset($params[0]);
		if ($params) {
			$action_params = $this->getActionParams();
			$params = \array_values($params);
			foreach ($params as $index => $param) {
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
