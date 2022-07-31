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
    protected ?string $origin;

    /**
     * RouteNotFound constructor.
     *
     * @param string|null $origin The Route Not Found origin
     */
    public function __construct(string $origin = null)
    {
        $this->origin = $origin;
    }

    /**
     * @return string|null
     */
    public function getOrigin() : ?string
    {
        return $this->origin;
    }
}
