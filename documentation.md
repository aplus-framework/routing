# Routing Library *documentation*

The Routing library makes it possible to route a URL to a particular method or
closure, with named routes.

The router is responsible for handling the various routes of an application,
created in a route collection.

```php
use Framework\Routing\Collection;
use Framework\Routing\Router;

$router = new Router();

// Defines a collection of routes for the Origin "http://domain.tld"
$router->serve('http://domain.tld', static function (Collection $routes) {
    // Route the "/" path to App\Controllers\Home::index method on GET requests
    $routes->get('/', 'App\Controllers\Home::index');
    // Route the "/contact" path to App\Controllers\Contact::index method on GET requests
    $routes->get('/contact', 'App\Controllers\Contact::index');
    // Route the "/contact" path to App\Controllers\Contact::create method on POST requests
    $routes->post('/contact', 'App\Controllers\Contact::create');
});

// Match the current Route
$route = $router->match($_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
// Run the route. Call the method or closure
$route->run();
```

## Collection

The collection has several methods for creating routes.

Most receive the name of the HTTP method to which the route is assigned.

For example, the HTTP GET method has the `get` method. The POST, `post`, etc.

```php
// Named route "home"
$routes->get('/', 'App\Controllers\Home::index', 'home');
// Route with Closure instead of method in class
$routes->get('/test', static function () {
    return 'Hello world!';
}, 'test');
// Different HTTP Methods using placeholders
$routes->get('/user', 'App\Users::index');
$routes->post('/user', 'App\Users::create');
$routes->get('/user/{int}', 'App\Users::show/0');
$routes->patch('/user/{int}', 'App\Users::update/0');
$routes->put('/user/{int}', 'App\Users::replace/0');
$routes->delete('/user/{int}', 'App\Users::delete/0');
   ```

### Placeholders

A path can take placeholders to make it dynamic. So that 
`/users/1` is within `/users/{int}`.

The available placeholders are:

| Placeholder | Utility |
| --- | --- |
| {alpha} | Accepts alphabetic characters |
| {alphanum} | Accepts alphanumeric characters |
| {any} | Accepts any characters |
| {int} | Accepts valid integers in PHP |
| {num} | Accept numbers |
| {md5} | Accept a md5 hash |
| {port} | Accepts valid port number |
| {scheme} | Accepts HTTP or HTTPS schema |
| {segment} | Accepts a segment of a URL path |
| {subdomain} | Accepts a subdomain |
| {title} | Accepts title format |

### Resources

To quickly create an API resource, you can do this:

```php
$routes->resource('/users', 'App\Users', 'users');
```

Which will create 6 routes.

| HTTP Method | Path | PHP Method | Name |
| --- | --- | --- | --- |
| GET | /users | App\Users::index | users.index |
| POST | /users | App\Users::create | users.create |
| GET | /users/{int} | App\Users::show/0 | users.show |
| PATCH | /users/{int} | App\Users::update/0 | users.update |
| PUT | /users/{int} | App\Users::replace/0 | users.replace |
| DELETE | /users/{int} | App\Users::delete/0 | users.delete |

If you want to make web pages available to manipulate the resource, you can create
a web resource. Which will create the previous 6 routes plus 4 web routes:

```php
$routes->webResource('/users', 'App\Users', 'users');
```

| HTTP Method | Path | PHP Method | Name |
| --- | --- | --- | --- |
| GET | /users/new | App\Users::new | users.web_new |
| GET | /users/{int}/edit | App\Users::edit/0 | users.web_edit |
| POST | /users/{int}/delete | App\Users::delete/0 | users.web_delete |
| POST | /users/{int}/update | App\Users::update/0 | users.web_update |

### Groups

Sometimes the route path can become repetitive and to simplify route creation 
you can group them by URL path.

```php
$routes->group('/blog',[
    // Route for "/blog/"
    $routes->get('/','App\Blog\Posts::index'),
    // Rout for "/blog/{title}"
    $routes->get('/{title}','App\Blog\Posts::show/0'),
]);
```

Grouping works on multiple layers. This also works:

```php
$routes->group('/blog',[
    // Route for "/blog/"
    $routes->get('/','App\Blog\Posts::index'),
    $routes->group('/posts',[
        // Route for "/blog/posts/"
        $routes->get('/','App\Blog\Posts::index'),
        // Route for "/blog/posts/{title}"
        $routes->get('/{title}','App\Blog\Posts::show/0'),
    ])
]);
```

### Namespaces

The namespace of some routes may become repetitive to type. 
For simplicity, you can use the namespace:

```php
$routes->namespace('App\Controllers',[
    // App\Controllers\Users::index
    $routes->get('/user', 'Users::index'),
    $routes->post('/user', 'Users::create'),
    $routes->get('/user/{int}', 'Users::show/0'),
    $routes->patch('/user/{int}', 'Users::update/0'),
    $routes->put('/user/{int}', 'Users::replace/0'),
    $routes->delete('/user/{int}', 'Users::delete/0'),
    $routes->group('/posts',[
        $routes->get('/','Blog\Posts::index'),
        // App\Controllers\Blog\Posts::show/0
        $routes->get('/{title}','Blog\Posts::show/0'),
    ])
]);
```

### Route Not Found

Each collection can have its own custom Error 404 page. Just set the
path to the controller class or a closure.

```php
$routes->notFound(static function () {
    return '<h1>Error 404</h1>';
});
// or
$routes->notFound('App\Controllers\Errors::notFound');
```

## Router

The router is where several collections are stored, and it is decided which
route will be executed according to the HTTP method and request URL.

```php
$router->serve('http://domain.tld', static function (Collection $routes) {
    // Routes ...
});
$router->serve('http://other.xyz', static function (Collection $routes) {
    // Routes ...
});
```

### Error 404

If a collection does not have an Error 404 route set, the default router page
will be responded. You can also customize it:

```php
$router->setDefaultRouteNotFound('App\Errors::notFound');
```

### Named Routes

Routes in a collection can be named for easy route maintenance when a URL
changes its path.

Through the Router you can take routes by name:

```php
$route = $router->getNamedRoute('home');
```

### Matched Route

After calling the `match` method, you can get the route with `getMatchedRoute`:

```php
$route = $router->getMatchedRoute();
```

### HTTP OPTIONS Method

The HTTP OPTIONS method serves to show which methods a particular resource 
makes available. With the routes defined, the server can answer automatically 
which methods are allowed.

```php
$router->setAutoOptions(true);
```
