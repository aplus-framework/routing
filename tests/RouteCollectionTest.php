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
use Framework\Routing\Route;
use Framework\Routing\RouteCollection;
use Framework\Routing\Router;
use PHPUnit\Framework\TestCase;

/**
 * Class RouteCollectionTest.
 */
final class RouteCollectionTest extends TestCase
{
    protected RouteCollection $collection;
    protected Router $router;

    protected function setUp() : void
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'domain.tld';
        $_SERVER['REQUEST_URI'] = '/';
        $this->router = new Router(new Response(new Request()));
        $this->collection = new RouteCollection(
            $this->router,
            'http://domain.tld'
        );
    }

    public function testGet() : void
    {
        self::assertSame([], $this->collection->routes);
        $this->collection->get('/', 'RouteActions::foo');
        self::assertArrayHasKey('GET', $this->collection->routes);
    }

    public function testPost() : void
    {
        self::assertSame([], $this->collection->routes);
        $this->collection->post('/', 'RouteActions::foo');
        self::assertArrayHasKey('POST', $this->collection->routes);
    }

    public function testPut() : void
    {
        self::assertSame([], $this->collection->routes);
        $this->collection->put('/', 'RouteActions::foo');
        self::assertArrayHasKey('PUT', $this->collection->routes);
    }

    public function testPatch() : void
    {
        self::assertSame([], $this->collection->routes);
        $this->collection->patch('/', 'RouteActions::foo');
        self::assertArrayHasKey('PATCH', $this->collection->routes);
    }

    public function testDelete() : void
    {
        self::assertSame([], $this->collection->routes);
        $this->collection->delete('/', 'RouteActions::foo');
        self::assertArrayHasKey('DELETE', $this->collection->routes);
    }

    public function testOptions() : void
    {
        self::assertSame([], $this->collection->routes);
        $this->collection->options('/', 'RouteActions::foo');
        self::assertArrayHasKey('OPTIONS', $this->collection->routes);
    }

    public function testNotFound() : void
    {
        $this->router->match();
        self::assertNull($this->collection->getRouteNotFound()); // @phpstan-ignore-line
        $this->collection->notFound('Errors::notFound');
        // @phpstan-ignore-next-line
        self::assertInstanceOf(Route::class, $this->collection->getRouteNotFound());
    }

    public function testResource() : void
    {
        self::assertSame([], $this->collection->routes);
        $this->collection->resource('/users', 'Users', 'users');
        self::assertSame([
            'GET',
            'POST',
            'PATCH',
            'PUT',
            'DELETE',
        ], \array_keys($this->collection->routes));
        self::assertSame('users.index', $this->collection->routes['GET'][0]->getName());
        self::assertSame('users.create', $this->collection->routes['POST'][0]->getName());
        self::assertSame('users.show', $this->collection->routes['GET'][1]->getName());
        self::assertSame('users.update', $this->collection->routes['PATCH'][0]->getName());
        self::assertSame('users.replace', $this->collection->routes['PUT'][0]->getName());
        self::assertSame('users.delete', $this->collection->routes['DELETE'][0]->getName());
    }

    public function testResourceWithExcept() : void
    {
        self::assertSame([], $this->collection->routes);
        $this->collection->resource('/users', 'Users', 'users', ['create', 'show', 'delete']);
        self::assertSame([
            'GET',
            'PATCH',
            'PUT',
        ], \array_keys($this->collection->routes));
    }

    public function testPresenter() : void
    {
        self::assertSame([], $this->collection->routes);
        $this->collection->presenter('/admin/posts', 'Admin\\Posts', 'admin.posts');
        self::assertSame([
            'GET',
            'POST',
        ], \array_keys($this->collection->routes));
        self::assertSame('admin.posts.index', $this->collection->routes['GET'][0]->getName());
        self::assertSame('admin.posts.new', $this->collection->routes['GET'][1]->getName());
        self::assertSame('admin.posts.create', $this->collection->routes['POST'][0]->getName());
        self::assertSame('admin.posts.show', $this->collection->routes['GET'][2]->getName());
        self::assertSame('admin.posts.edit', $this->collection->routes['GET'][3]->getName());
        self::assertSame('admin.posts.update', $this->collection->routes['POST'][1]->getName());
        self::assertSame('admin.posts.remove', $this->collection->routes['GET'][4]->getName());
        self::assertSame('admin.posts.delete', $this->collection->routes['POST'][2]->getName());
    }

    public function testPresenterWithExcept() : void
    {
        self::assertSame([], $this->collection->routes);
        $this->collection->presenter('/admin/posts', 'Admin\\Posts', 'admin.posts', [
            'create',
            'update',
            'delete',
        ]);
        self::assertSame([
            'GET',
        ], \array_keys($this->collection->routes));
    }

    public function testCount() : void
    {
        self::assertCount(0, $this->collection);
        $this->collection->resource('/users', 'Users', 'users');
        self::assertCount(6, $this->collection);
        $this->collection->presenter('/posts', 'Posts', 'posts');
        self::assertCount(14, $this->collection);
        $this->collection->notFound('Errors::notFound');
        self::assertCount(15, $this->collection);
    }

    public function testGroup() : void
    {
        $this->collection->group('/admin', [
            $this->collection->get('/', 'Admin::index'),
        ]);
        self::assertSame('/admin', $this->collection->routes['GET'][0]->getPath());
    }

    public function testGroupWithSubgroups() : void
    {
        $this->collection->group('/users', [
            $this->collection->get('/', 'Users::index'),
            $this->collection->group('/{uuid}', [
                $this->collection->get('/', 'Users::show/0'),
                $this->collection->get('/edit', 'Users::edit/0'),
                $this->collection->group('/projects/', [
                    $this->collection->presenter('/', 'UserProjects', 'users.projects'),
                ]),
            ]),
        ]);
        self::assertSame('/users', $this->collection->routes['GET'][0]->getPath());
        self::assertSame('/users/{uuid}', $this->collection->routes['GET'][1]->getPath());
        self::assertSame('/users/{uuid}/edit', $this->collection->routes['GET'][2]->getPath());
        self::assertSame('/users/{uuid}/projects', $this->collection->routes['GET'][3]->getPath());
        self::assertSame(
            '/users/{uuid}/projects/new',
            $this->collection->routes['GET'][4]->getPath()
        );
        self::assertSame(
            '/users/{uuid}/projects/{int}',
            $this->collection->routes['GET'][5]->getPath()
        );
    }

    public function testGroupWithOptions() : void
    {
        $this->collection->group('/users', [
            $this->collection->get('/', 'Users::index'),
            $this->collection->group('/{uuid}', [
                $this->collection->get('/', 'Users::show/0')
                    ->setOptions(['permissions' => ['edit']]),
                $this->collection->get('/edit', 'Users::edit/0'),
                $this->collection->group('/projects/', [
                    $this->collection->presenter('/', 'UserProjects', 'users.projects'),
                ], ['foo' => 'bar']),
            ]),
        ], ['permissions' => ['show']]);
        self::assertSame(
            ['permissions' => ['show']],
            $this->collection->routes['GET'][0]->getOptions()
        );
        self::assertSame(
            ['permissions' => ['edit']],
            $this->collection->routes['GET'][1]->getOptions()
        );
        self::assertSame(
            ['permissions' => ['show']],
            $this->collection->routes['GET'][2]->getOptions()
        );
        self::assertSame(
            ['permissions' => ['show'], 'foo' => 'bar'],
            $this->collection->routes['GET'][3]->getOptions()
        );
        self::assertSame(
            ['permissions' => ['show'], 'foo' => 'bar'],
            $this->collection->routes['GET'][4]->getOptions()
        );
    }

    public function testNamespace() : void
    {
        $this->collection->namespace('\App\Controllers\\', [
            $this->collection->get('/', 'Home::index'),
            $this->collection->namespace('\Blog', [
                $this->collection->group('/blog', [
                    $this->collection->resource('/', 'Blog', 'blog'),
                ]),
            ]),
        ]);
        self::assertSame(
            'App\Controllers\Home::index',
            $this->collection->routes['GET'][0]->getAction()
        );
        self::assertSame(
            'App\Controllers\Blog\Blog::index/*',
            $this->collection->routes['GET'][1]->getAction()
        );
        self::assertSame(
            'App\Controllers\Blog\Blog::show/*',
            $this->collection->routes['GET'][2]->getAction()
        );
    }

    public function testRedirect() : void
    {
        $response = $this->router->getResponse();
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getStatusReason());
        $this->collection->redirect(
            '/search',
            'https://www.google.com/search?q=site:domain.tld'
        )->run();
        self::assertSame(
            'https://www.google.com/search?q=site:domain.tld',
            $response->getHeader('Location')
        );
        self::assertSame(307, $response->getStatusCode());
        self::assertSame('Temporary Redirect', $response->getStatusReason());
    }

    public function testMethodNotAllowed() : void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Method not allowed: ' . $this->collection::class . '::setOrigin'
        );
        $this->collection->setOrigin('http://domain.tld'); // @phpstan-ignore-line
    }

    public function testMethodNotFound() : void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Method not found: ' . $this->collection::class . '::bazz'
        );
        $this->collection->bazz(); // @phpstan-ignore-line
    }

    public function testGetProperties() : void
    {
        self::assertIsString($this->collection->origin);
        self::assertInstanceOf(Router::class, $this->collection->router);
        self::assertIsArray($this->collection->routes);
        $this->expectException(\Error::class);
        $this->expectExceptionMessage(
            'Cannot access property ' . $this->collection::class . '::$foo'
        );
        $foo = $this->collection->foo; // @phpstan-ignore-line
    }
}
