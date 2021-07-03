<?php declare(strict_types=1);
/*
 * This file is part of The Framework Routing Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\Routing\Attributes;

use Attribute;

/**
 * Class Route.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route
{
	/**
	 * Route constructor.
	 *
	 * @param array|string $methods The Route HTTP Methods
	 * @param string $path The Route path
	 * @param array $argumentsOrder The Route path arguments order
	 * @param string|null $name The Route name
	 * @param string|null $origin The Route origin
	 */
	public function __construct(
		array | string $methods,
		string $path,
		array $argumentsOrder = [],
		string $name = null,
		string $origin = null,
	) {
	}
}
