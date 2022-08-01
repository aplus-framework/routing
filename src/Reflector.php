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
use Framework\Routing\Attributes\RouteNotFound;
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
     * @return array<Route>
     */
    protected function getMethodRoutes(string $method) : array
    {
        $reflectionMethod = $this->reflection->getMethod($method);
        $routes = [];
        foreach ($reflectionMethod->getAttributes() as $attribute) {
            if ($attribute->getName() === Route::class) {
                $routes[] = $attribute->newInstance();
            }
        }
        return $routes; // @phpstan-ignore-line
    }

    /**
     * @template T of object
     *
     * @param ReflectionClass<T> $reflection
     *
     * @return array<int,string>
     */
    protected function getObjectOrigins(ReflectionClass $reflection) : array
    {
        $origins = [];
        foreach ($reflection->getAttributes() as $attribute) {
            if ($attribute->getName() === Origin::class) {
                /**
                 * @var Origin $origin
                 */
                $origin = $attribute->newInstance();
                $origins[] = $origin->getOrigin();
            }
        }
        $parent = $reflection->getParentClass();
        if ($parent) {
            $origins = [...$origins, ...$this->getObjectOrigins($parent)];
        }
        $origins = \array_unique($origins);
        \sort($origins);
        return $origins;
    }

    /**
     * @throws ReflectionException
     *
     * @return array<mixed>
     */
    public function getRoutes() : array
    {
        $origins = $this->getObjectOrigins($this->reflection);
        $result = [];
        foreach ($this->reflection->getMethods() as $method) {
            if ( ! $method->isPublic()) {
                continue;
            }
            $routes = $this->getMethodRoutes($method->getName());
            if (empty($routes)) {
                continue;
            }
            foreach ($routes as $route) {
                $result[] = [
                    'origins' => $route->getOrigins() ?: $origins,
                    'methods' => $route->getMethods(),
                    'path' => $route->getPath(),
                    'arguments' => $route->getArguments(),
                    'name' => $route->getName(),
                    'action' => $this->reflection->name . '::' . $method->name,
                ];
            }
        }
        return $result;
    }

    /**
     * @param string $method
     *
     * @throws ReflectionException
     *
     * @return array<RouteNotFound>
     */
    protected function getMethodRoutesNotFound(string $method) : array
    {
        $reflectionMethod = $this->reflection->getMethod($method);
        $routes = [];
        foreach ($reflectionMethod->getAttributes() as $attribute) {
            if ($attribute->getName() === RouteNotFound::class) {
                $routes[] = $attribute->newInstance();
            }
        }
        return $routes; // @phpstan-ignore-line
    }

    /**
     * @throws ReflectionException
     *
     * @return array<mixed>
     */
    public function getRoutesNotFound() : array
    {
        $origins = $this->getObjectOrigins($this->reflection);
        $result = [];
        foreach ($this->reflection->getMethods() as $method) {
            if ( ! $method->isPublic()) {
                continue;
            }
            $routes = $this->getMethodRoutesNotFound($method->getName());
            if (empty($routes)) {
                continue;
            }
            foreach ($routes as $route) {
                $result[] = [
                    'origins' => $route->getOrigins() ?: $origins,
                    'action' => $this->reflection->name . '::' . $method->name,
                ];
            }
        }
        return $result;
    }
}
