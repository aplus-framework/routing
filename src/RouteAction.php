<?php namespace Framework\Routing;

/**
 * Class RouteAction.
 */
abstract class RouteAction
{
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

	/**
	 * Runs just before the action method and after the constructor.
	 *
	 * Used to prepare settings, filter input data, acts as a middleware between
	 * the routing and the action method.
	 *
	 * @param string $action Route action, the method name
	 * @param array  $params Route params, the method arguments
	 *
	 * @return mixed Returns a response to stop the route action execution or null to continue the
	 *               process and call the action method
	 */
	protected function beforeAction(string $action, array $params = [])
	{
		// Prepare or intercept...
	}

	/**
	 * Runs just after the action method and before the desconstructor.
	 *
	 * Used to finalize settings, filter output data, acts as a middleware between
	 * the action method and the final response.
	 *
	 * @param string $action Route action, the method name
	 * @param array  $params Route params, the method arguments
	 *
	 * @return mixed
	 */
	protected function afterAction(string $action, array $params = [])
	{
		// Finalize...
	}
}
