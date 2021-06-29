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
 * Class RouteAction.
 */
abstract class RouteAction
{
	protected string $actionMethod;
	/**
	 * @var array<int,string>
	 */
	protected array $actionParams;
	protected bool $actionRun;

	public function __call($method, $arguments)
	{
		if ($method === 'beforeAction') {
			return $this->beforeAction(...$arguments);
		}
		if ($method === 'afterAction') {
			return $this->afterAction(...$arguments);
		}
		if (\method_exists($this, $method)) {
			throw new \BadMethodCallException("Action method not allowed: {$method}");
		}
		throw new \BadMethodCallException("Action method not found: {$method}");
	}

	public function __set($property, $value) : void
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
	 * @return mixed|void Returns a response to stop the route action execution or null to continue the
	 *                    process and call the action method
	 */
	protected function beforeAction()
	{
		// Prepare or intercept...
	}

	/**
	 * Runs just after the action method and before the deconstruct.
	 *
	 * Used to finalize settings, filter output data, acts as a middleware between
	 * the action method and the final response.
	 *
	 * @return mixed|void
	 */
	protected function afterAction(mixed $response)
	{
		return $response;
	}
}
