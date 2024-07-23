<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework Routing Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\Routing\Debug;

use Framework\Debug\Collection;

/**
 * Class RoutingCollection.
 *
 * @package routing
 */
class RoutingCollection extends Collection
{
    protected string $iconPath = __DIR__ . '/icons/routing.svg';
}
