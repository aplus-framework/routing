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

use Framework\Routing\Attributes\Origin;
use Framework\Routing\Attributes\Route;
use ReflectionClass;
use ReflectionException;

/**
 * Class Reflector.
 *
 * @package routing
 */
class Reflector
{
    /**
     * @var ReflectionClass<object>
     */
    protected ReflectionClass $reflection;

    /**
     * @template T of object
     *
     * @param class-string<T>|object $routeActions
     *
     * @throws ReflectionException
     */
    public function __construct(object | string $routeActions)
    {
        $this->reflection = new ReflectionClass($routeActions); // @phpstan-ignore-line
    }

    /**
     * @param string $method
     *
     * @throws ReflectionException
     *
     * @return Route|null
     */
    protected function getMethodRoute(string $method) : ?Route
    {
        $reflectionMethod = $this->reflection->getMethod($method);
        $route = null;
        foreach ($reflectionMethod->getAttributes() as $attribute) {
            if ($attribute->getName() === Route::class) {
                /**
                 * @var Route $route
                 */
                $route = $attribute->newInstance();
            }
        }
        return $route;
    }

    /**
     * @return array<int,string>
     */
    protected function getObjectOrigins() : array
    {
        $origins = [];
        foreach ($this->reflection->getAttributes() as $attribute) {
            if ($attribute->getName() === Origin::class) {
                /**
                 * @var Origin $origin
                 */
                $origin = $attribute->newInstance();
                $origins[] = $origin->getOrigin();
            }
        }
        return $origins;
    }

    /**
     * @throws ReflectionException
     *
     * @return array<mixed>
     */
    public function getRoutes() : array
    {
        $origins = $this->getObjectOrigins();
        $routes = [];
        foreach ($this->reflection->getMethods() as $method) {
            if ( ! $method->isPublic()) {
                continue;
            }
            $route = $this->getMethodRoute($method->getName());
            if ($route === null) {
                continue;
            }
            $origin = $route->getOrigin();
            $origin = $origin === null ? $origins : [$origin];
            $routes[] = [
                'origins' => $origin,
                'methods' => $route->getMethods(),
                'path' => $route->getPath(),
                'arguments' => $route->getArguments(),
                'name' => $route->getName(),
                'action' => $method->class . '::' . $method->name,
            ];
        }
        return $routes;
    }
}
