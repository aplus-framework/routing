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

use Framework\Routing\Attributes\Route;
use Framework\Routing\PresenterInterface;
use Framework\Routing\RouteActions;

/**
 * Class UsersRouteActionsPresenter.
 */
class UsersRouteActionsPresenter extends RouteActions implements PresenterInterface
{
    #[Route('GET', '/users')]
    public function index() : string
    {
        return __METHOD__;
    }

    #[Route('GET', '/users/new')]
    public function new() : string
    {
        return __METHOD__;
    }

    #[Route('POST', '/users')]
    public function create() : string
    {
        return __METHOD__;
    }

    #[Route('GET', '/users/{int}')]
    public function show(string $id) : string
    {
        return __METHOD__ . '/' . $id;
    }

    #[Route('GET', '/users/{int}/edit')]
    public function edit(string $id) : string
    {
        return __METHOD__ . '/' . $id;
    }

    #[Route('POST', '/users/{int}/update')]
    public function update(string $id) : string
    {
        return __METHOD__ . '/' . $id;
    }

    #[Route('GET', '/users/{int}/remove')]
    public function remove(string $id) : string
    {
        return __METHOD__ . '/' . $id;
    }

    #[Route('POST', '/users/{int}/delete')]
    public function delete(string $id) : string
    {
        return __METHOD__ . '/' . $id;
    }
}
