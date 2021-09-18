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
 * Class Origin.
 *
 * @package routing
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Origin
{
    protected string $origin;

    /**
     * Origin constructor.
     *
     * @param string $origin The Route origin
     */
    public function __construct(string $origin)
    {
        $this->origin = $origin;
    }

    public function getOrigin() : string
    {
        return $this->origin;
    }
}
