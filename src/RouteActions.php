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

/**
 * Class RouteActions.
 */
abstract class RouteActions
{
	protected string $actionMethod;
	/**
	 * @var array<int,string>
	 */
	protected array $actionParams;
	protected bool $actionRun;

	/**
	 * @param string $method
	 * @param array<int,mixed> $arguments
	 *
	 * @return mixed
	 */
	public function __call(string $method, array $arguments) : mixed
	{
		if ($method === 'beforeAction') {
			return $this->beforeAction();
		}
		if ($method === 'afterAction') {
			return $this->afterAction(...$arguments);
		}
		if (\method_exists($this, $method)) {
			throw new \BadMethodCallException("Action method not allowed: {$method}");
		}
		throw new \BadMethodCallException("Action method not found: {$method}");
	}

	public function __set(string $property, mixed $value) : void
	{
		if (\in_array($property, [
			'actionMethod',
			'actionParams',
			'actionRun',
		])) {
			$this->{$property} = $value;
			return;
		}
		throw new \Error(
			'Cannot access property ' . \get_class($this) . '::$' . $property
		);
	}

	/**
	 * Runs just before the action method and after the constructor.
	 *
	 * Used to prepare settings, filter input data, acts as a middleware between
	 * the routing and the action method.
	 *
	 * @return mixed Returns a response (any value, except null) to prevent the
	 * route action execution or null to continue the process and call the
	 * action method
	 */
	protected function beforeAction() : mixed
	{
		// Prepare or intercept...
		return null;
	}

	/**
	 * Runs just after the action method and before the deconstruct.
	 *
	 * Used to finalize settings, filter output data, acts as a middleware between
	 * the action method and the final response.
	 *
	 * @param mixed $response The returned value directly from beforeAction or
	 * from the action method, if it was executed. Use the $actionRun property
	 * to know if the action method was executed.
	 *
	 * @return mixed
	 */
	protected function afterAction(mixed $response) : mixed
	{
		return $response;
	}
}
