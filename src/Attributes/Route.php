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
     * @var array<int,string>
     */
    protected array $methods;
    protected string $path;
    /**
     * @var array<int,int>
     */
    protected array $argumentsOrder;
    protected ?string $name;
    protected ?string $origin;

    /**
     * Route constructor.
     *
     * @param array<int,string>|string $methods The Route HTTP Methods
     * @param string $path The Route path
     * @param array<int,int> $argumentsOrder The Route path arguments order
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
        $methods = (array) $methods;
        foreach ($methods as &$method) {
            $method = \strtoupper($method);
        }
        unset($method);
        $this->methods = $methods;
        $this->path = $path;
        $this->argumentsOrder = $argumentsOrder;
        $this->name = $name;
        $this->origin = $origin;
    }

    /**
     * @return array<int,string>
     */
    public function getMethods() : array
    {
        return $this->methods;
    }

    /**
     * @return string
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * @return array<int,int>
     */
    public function getArgumentsOrder() : array
    {
        return $this->argumentsOrder;
    }

    /**
     * @return string|null
     */
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getOrigin() : ?string
    {
        return $this->origin;
    }
}
