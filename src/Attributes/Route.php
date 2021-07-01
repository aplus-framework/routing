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
#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
class Route
{
	protected string | null $name;
	protected array $methods;
	protected string $path;
	protected array $paramsOrder;

	/**
	 * Route constructor.
	 *
	 * @param string|null $name The Route name
	 * @param array|string $methods The Route HTTP Methods
	 * @param string $path The Route path
	 * @param array $paramsOrder The Route path parameters order
	 */
	public function __construct(
		string | null $name,
		array | string $methods,
		string $path,
		array $paramsOrder = []
	) {
		$this->name = $name;
		$this->methods = (array) $methods;
		$this->path = $path;
		$this->paramsOrder = $paramsOrder;
	}
}
