<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework Routing Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Routing\Support;

use Framework\Routing\Attributes\Origin;
use Framework\Routing\Attributes\Route;

#[Origin('http://bar.xyz')]
abstract class AbstractClass
{
    #[Route('GET', '/hello')]
    public function hello() : void
    {
    }

    #[Route('GET', '/replace-origin', origins: 'xxx')]
    public function replaceOrigin() : void
    {
    }

    #[Route('GET', '/replace-origin-2', origins: ['xxx', 'yyy'])]
    public function replaceOrigin2() : void
    {
    }
}
