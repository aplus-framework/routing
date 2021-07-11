<?php
/*
 * This file is part of The Framework Routing Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Routing\Support;

use Framework\Routing\RouteActions;

class WithRouteActions extends RouteActions
{
    /**
     * @var array<int,mixed>
     */
    protected array $construct;

    public function __construct(mixed ...$construct)
    {
        $this->construct = $construct;
    }

    public function index(string ...$params) : string
    {
        return \implode(', ', [...$params, ...$this->construct]);
    }

    protected function notAllowed() : void
    {
        //
    }
}
