<?php
/*
 * This file is part of The Framework Routing Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Routing;

use Attribute;
use Framework\Routing\Attributes\Route;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionObject;
use Tests\Routing\Support\UsersRouteActionsResource;

/**
 * Class RouteAttributeTest.
 */
final class RouteAttributeTest extends TestCase
{
    /**
     * @param string $method
     * @param array<int,mixed> $arguments
     *
     * @throws ReflectionException
     */
    protected function assertAttribute(string $method, array $arguments) : void
    {
        $reflection = new ReflectionObject(new UsersRouteActionsResource());
        $method = $reflection->getMethod($method);
        $attributes = $method->getAttributes();
        $routeAttribute = $attributes[0];
        self::assertSame(Route::class, $routeAttribute->getName());
        self::assertSame($arguments, $routeAttribute->getArguments());
        self::assertSame(Attribute::TARGET_METHOD, $routeAttribute->getTarget());
        self::assertFalse($routeAttribute->isRepeated());
        /**
         * @var Route $instance
         */
        $instance = $routeAttribute->newInstance();
        self::assertSame([$arguments[0]], $instance->getMethods());
        self::assertSame($arguments[1], $instance->getPath());
        self::assertSame([], $instance->getArgumentsOrder());
        self::assertNull($instance->getName());
        self::assertNull($instance->getOrigin());
    }

    public function testIndex() : void
    {
        $this->assertAttribute('index', ['GET', '/users']);
    }

    public function testCreate() : void
    {
        $this->assertAttribute('create', ['POST', '/users']);
    }

    public function testShow() : void
    {
        $this->assertAttribute('show', ['GET', '/users/{int}']);
    }

    public function testUpdate() : void
    {
        $this->assertAttribute('update', ['PATCH', '/users/{int}']);
    }

    public function testReplace() : void
    {
        $this->assertAttribute('replace', ['PUT', '/users/{int}']);
    }

    public function testDelete() : void
    {
        $this->assertAttribute('delete', ['DELETE', '/users/{int}']);
    }
}
