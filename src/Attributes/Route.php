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
 * Class Route.
 *
 * @package routing
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route
{
    /**
     * @var array<int,string>
     */
    protected array $methods;
    protected string $path;
    protected string $arguments;
    protected ?string $name;
    /**
     * @var array<string>
     */
    protected array $origins;

    /**
     * Route constructor.
     *
     * @param array<int,string>|string $methods The Route HTTP Methods
     * @param string $path The Route path
     * @param string $arguments The Route action arguments
     * @param string|null $name The Route name
     * @param array<string>|string $origins The Route origins
     */
    public function __construct(
        array | string $methods,
        string $path,
        string $arguments = '*',
        string $name = null,
        array | string $origins = [],
    ) {
        $methods = (array) $methods;
        foreach ($methods as &$method) {
            $method = \strtoupper($method);
        }
        unset($method);
        $this->methods = $methods;
        $this->path = $path;
        $this->arguments = $arguments;
        $this->name = $name;
        $this->origins = (array) $origins;
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
     * @return string
     */
    public function getArguments() : string
    {
        return $this->arguments;
    }

    /**
     * @return string|null
     */
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * @return array<string>
     */
    public function getOrigins() : array
    {
        return $this->origins;
    }
}
