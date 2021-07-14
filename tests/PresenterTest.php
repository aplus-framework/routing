<?php
/*
 * This file is part of Aplus Framework Routing Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Routing;

use Framework\HTTP\Request;
use Framework\HTTP\Response;
use Framework\Routing\RouteCollection;
use Framework\Routing\Router;
use PHPUnit\Framework\TestCase;
use Tests\Routing\Support\UsersRouteActionsPresenter;

/**
 * Class PresenterTest.
 */
final class PresenterTest extends TestCase
{
    protected Response $response;
    protected Router $router;

    protected function setUp() : void
    {
        $this->prepare([
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'admin.domain.tld',
            'REQUEST_URI' => '/users',
        ]);
    }

    /**
     * @param array<string,mixed> $server
     */
    protected function prepare(array $server = []) : void
    {
        foreach ($server as $key => $value) {
            $_SERVER[$key] = $value;
        }
        $this->response = new Response(new Request());
        $this->router = new Router($this->response);
        $this->router->serve(
            'http://admin.domain.tld',
            static function (RouteCollection $routes) : void {
                $routes->presenter('/users', UsersRouteActionsPresenter::class, 'admin.users');
            }
        );
    }

    public function testIndex() : void
    {
        self::assertSame(
            UsersRouteActionsPresenter::class . '::index',
            $this->router->match()->run()->getBody()
        );
        self::assertSame('admin.users.index', $this->router->getMatchedRoute()->getName());
    }

    public function testNew() : void
    {
        $this->prepare([
            'REQUEST_URI' => '/users/new',
        ]);
        self::assertSame(
            UsersRouteActionsPresenter::class . '::new',
            $this->router->match()->run()->getBody()
        );
        self::assertSame('admin.users.new', $this->router->getMatchedRoute()->getName());
    }

    public function testCreate() : void
    {
        $this->prepare([
            'REQUEST_METHOD' => 'POST',
        ]);
        self::assertSame(
            UsersRouteActionsPresenter::class . '::create',
            $this->router->match()->run()->getBody()
        );
        self::assertSame('admin.users.create', $this->router->getMatchedRoute()->getName());
    }

    public function testShow() : void
    {
        $this->prepare([
            'REQUEST_URI' => '/users/25',
        ]);
        self::assertSame(
            UsersRouteActionsPresenter::class . '::show/25',
            $this->router->match()->run()->getBody()
        );
        self::assertSame('admin.users.show', $this->router->getMatchedRoute()->getName());
    }

    public function testEdit() : void
    {
        $this->prepare([
            'REQUEST_URI' => '/users/25/edit',
        ]);
        self::assertSame(
            UsersRouteActionsPresenter::class . '::edit/25',
            $this->router->match()->run()->getBody()
        );
        self::assertSame('admin.users.edit', $this->router->getMatchedRoute()->getName());
    }

    public function testUpdate() : void
    {
        $this->prepare([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/users/25/update',
        ]);
        self::assertSame(
            UsersRouteActionsPresenter::class . '::update/25',
            $this->router->match()->run()->getBody()
        );
        self::assertSame('admin.users.update', $this->router->getMatchedRoute()->getName());
    }

    public function testRemove() : void
    {
        $this->prepare([
            'REQUEST_URI' => '/users/25/remove',
        ]);
        self::assertSame(
            UsersRouteActionsPresenter::class . '::remove/25',
            $this->router->match()->run()->getBody()
        );
        self::assertSame('admin.users.remove', $this->router->getMatchedRoute()->getName());
    }

    public function testDelete() : void
    {
        $this->prepare([
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/users/25/delete',
        ]);
        self::assertSame(
            UsersRouteActionsPresenter::class . '::delete/25',
            $this->router->match()->run()->getBody()
        );
        self::assertSame('admin.users.delete', $this->router->getMatchedRoute()->getName());
    }

    public function testOptions() : void
    {
        $this->prepare([
            'REQUEST_METHOD' => 'OPTIONS',
            'REQUEST_URI' => '/users/25',
        ]);
        $this->router->setAutoOptions();
        self::assertSame(
            '',
            $this->router->match()->run()->getBody()
        );
        self::assertSame(
            'GET, HEAD, OPTIONS',
            $this->response->getHeader('Allow')
        );
        self::assertSame('auto-allow-200', $this->router->getMatchedRoute()->getName());
    }
}
