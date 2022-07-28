<?php
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
use Framework\Routing\ResourceInterface;
use Framework\Routing\RouteActions;

/**
 * Class UsersRouteActionsResource.
 */
#[Origin('http://domain.com')]
#[Origin('http://api.domain.xyz')]
class UsersRouteActionsResource extends RouteActions implements ResourceInterface
{
    #[Route('GET', '/users', origin: 'http://foo.com')]
    public function index() : string
    {
        return __METHOD__;
    }

    #[Route('POST', '/users')]
    public function create() : string
    {
        return __METHOD__;
    }

    #[Route('GET', '/users/{int}', '0')]
    public function show(string $id) : string
    {
        return __METHOD__ . '/' . $id;
    }

    #[Route('PATCH', '/users/{int}', '0')]
    public function update(string $id) : string
    {
        return __METHOD__ . '/' . $id;
    }

    #[Route('PUT', '/users/{int}', '0')]
    public function replace(string $id) : string
    {
        return __METHOD__ . '/' . $id;
    }

    #[Route('DELETE', '/users/{int}', '0', 'users.delete')]
    public function delete(string $id) : string
    {
        return __METHOD__ . '/' . $id;
    }
}
