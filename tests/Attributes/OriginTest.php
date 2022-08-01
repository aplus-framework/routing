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
use Framework\Routing\Attributes\Origin;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Tests\Routing\Support\UsersRouteActionsResource;

/**
 * Class OriginTest.
 */
final class OriginTest extends TestCase
{
    public function testAttributes() : void
    {
        $reflection = new ReflectionObject(new UsersRouteActionsResource());
        $attributes = $reflection->getAttributes();
        $origin = $attributes[0];
        self::assertSame(Origin::class, $origin->getName());
        self::assertSame(['http://domain.com'], $origin->getArguments());
        self::assertSame(Attribute::TARGET_CLASS, $origin->getTarget());
        self::assertTrue($origin->isRepeated());
        /**
         * @var Origin $instance
         */
        $instance = $origin->newInstance();
        self::assertSame('http://domain.com', $instance->getOrigin());
        $origin = $attributes[1];
        self::assertSame(Origin::class, $origin->getName());
        self::assertSame(['http://api.domain.xyz'], $origin->getArguments());
        self::assertSame(Attribute::TARGET_CLASS, $origin->getTarget());
        self::assertTrue($origin->isRepeated());
        /**
         * @var Origin $instance
         */
        $instance = $origin->newInstance();
        self::assertSame('http://api.domain.xyz', $instance->getOrigin());
    }
}
