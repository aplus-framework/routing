<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework Routing Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\Routing\Attributes;

use Attribute;

/**
 * Class RouteNotFound.
 *
 * @package routing
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class RouteNotFound
{
    /**
     * @var array<string>
     */
    protected array $origins;

    /**
     * RouteNotFound constructor.
     *
     * @param array<string>|string $origins The Route Not Found origins
     */
    public function __construct(array | string $origins = [])
    {
        $this->origins = (array) $origins;
    }

    /**
     * @return array<string>
     */
    public function getOrigins() : array
    {
        return $this->origins;
    }
}
