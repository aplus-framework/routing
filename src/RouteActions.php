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

use BadMethodCallException;

/**
 * Class RouteActions.
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
     * Runs just before the action method and after the constructor.
     *
     * Used to prepare settings, filter input data, acts as a middleware between
     * the routing and the action method.
     *
     * @param string $method The action method
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
     * Runs just after the action method and before the deconstruct.
     *
     * Used to finalize settings, filter output data, acts as a middleware between
     * the action method and the final response.
     *
     * @param string $method The action method
     * @param array<int,string> $arguments The action method arguments
     * @param bool $run Indicates if the action method was executed
     * @param mixed $result The returned value directly from beforeAction or
     * from the action method, if it was executed
     *
     * @return mixed
     */
    protected function afterAction(
        string $method,
        array $arguments,
        bool $run,
        mixed $result
    ) : mixed {
        return $result;
    }
}
