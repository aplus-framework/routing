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

/**
 * Class RouteActions.
 *
 * @package routing
 */
abstract class RouteActions
{
    /**
     * @param string $method
     * @param array<int,mixed> $arguments
     *
     * @return mixed
     */
    public function __call(string $method, array $arguments) : mixed
    {
        if ($method === 'beforeAction') {
            return $this->beforeAction(...$arguments);
        }
        if ($method === 'afterAction') {
            return $this->afterAction(...$arguments);
        }
        $class = static::class;
        if (\method_exists($this, $method)) {
            throw new BadMethodCallException(
                "Action method not allowed: {$class}::{$method}"
            );
        }
        throw new BadMethodCallException("Action method not found: {$class}::{$method}");
    }

    /**
     * Runs just before the class action method and after the constructor.
     *
     * Used to prepare settings, filter input data, acts as a middleware between
     * the routing and the class action method.
     *
     * @param string $method The action method name
     * @param array<int,string> $arguments The action method arguments
     *
     * @return mixed Returns a response (any value, except null) to prevent the
     * route action execution or null to continue the process and call the
     * action method
     */
    protected function beforeAction(string $method, array $arguments) : mixed
    {
        // Prepare or intercept...
        return null;
    }

    /**
     * Runs just after the class action method and before the destructor.
     *
     * Used to finalize settings, filter output data, acts as a middleware between
     * the action method and the final response.
     *
     * @param string $method The action method name
     * @param array<int,string> $arguments The action method arguments
     * @param bool $ran Indicates if the class action method was executed, true
     * if it was not intercepted by the beforeAction method
     * @param mixed $result The returned value directly from beforeAction or
     * from the class action method, if it was executed
     *
     * @see RouteActions::beforeAction()
     *
     * @return mixed
     */
    protected function afterAction(
        string $method,
        array $arguments,
        bool $ran,
        mixed $result
    ) : mixed {
        return $result;
    }
}
