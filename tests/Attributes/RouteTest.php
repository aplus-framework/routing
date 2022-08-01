<?php
/*
 * This file is part of Aplus Framework Routing Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Routing\Attributes;

use Attribute;
use Framework\Routing\Attributes\Route;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionObject;
use Tests\Routing\Support\UsersRouteActionsResource;

/**
 * Class RouteTest.
 */
final class RouteTest extends TestCase
{
    /**
     * @param string $method
     * @param array<mixed> $arguments
     *
     * @throws ReflectionException
     */
    protected function assertAttribute(string $method, array $arguments) : void
    {
        $reflection = new ReflectionObject(new UsersRouteActionsResource());
        $reflectionMethod = $reflection->getMethod($method);
        $attributes = $reflectionMethod->getAttributes();
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
        \in_array($method, ['index', 'create'])
            ? self::assertSame('*', $instance->getArguments())
            : self::assertSame('0', $instance->getArguments());
        $method === 'delete'
            ? self::assertSame('users.delete', $instance->getName())
            : self::assertNull($instance->getName());
        $method === 'index'
            ? self::assertSame('http://foo.com', $instance->getOrigin())
            : self::assertNull($instance->getOrigin());
    }

    public function testIndex() : void
    {
        $this->assertAttribute('index', [
            'GET',
            '/users',
            'origin' => 'http://foo.com',
        ]);
    }

    public function testCreate() : void
    {
        $this->assertAttribute('create', ['POST', '/users']);
    }

    public function testShow() : void
    {
        $this->assertAttribute('show', ['GET', '/users/{int}', '0']);
    }

    public function testUpdate() : void
    {
        $this->assertAttribute('update', ['PATCH', '/users/{int}', '0']);
    }

    public function testReplace() : void
    {
        $this->assertAttribute('replace', ['PUT', '/users/{int}', '0']);
    }

    public function testDelete() : void
    {
        $this->assertAttribute('delete', ['DELETE', '/users/{int}', '0', 'users.delete']);
    }
}
