Routing
=======

.. image:: image.png
    :alt: Aplus Framework Routing Library

Aplus Framework Routing Library.

- `Installation`_

Installation
------------

The installation of this library can be done with Composer:

.. code-block::

    composer require aplus/routing

Introduction
------------

The Routing Library makes it possible to route a URL to a particular class method or
Closure, with named routes.

The Router is responsible for handling the various Routes of an application,
created in a Route Collection.

.. code-block:: php

    use Framework\HTTP\Request;
    use Framework\HTTP\Response;
    use Framework\Routing\RouteCollection;
    use Framework\Routing\Router;
    
    $request = new Request();
    $response = new Response($request);
    $router = new Router($response);
    
    // Defines a Route Collection for the "http://domain.tld" origin
    $router->serve('http://domain.tld', function (RouteCollection $routes) {
        // Route "/" path to App\Controllers\Home::index method on GET requests
        $routes->get('/', 'App\Controllers\Home::index');
        // Route "/contact" path to App\Controllers\Contact::index method on GET requests
        $routes->get('/contact', 'App\Controllers\Contact::index');
        // Route "/contact" path to App\Controllers\Contact::create method on POST requests
        $routes->post('/contact', 'App\Controllers\Contact::create');
    });
    
    // Match the Route according to HTTP URL and method
    $route = $router->match();

    // Run the Route, calling a class method or a Closure and return the Response
    $response = $route->run();

    // Send the HTTP Response
    $response->send();

Placeholders
------------

A path can take placeholders to make it dynamic. So that ``/users/1`` is within
``/users/{int}``.

The available placeholders are:

+-----------------+---------------------------------+
| Placeholder     | Utility                         |
+=================+=================================+
| ``{alpha}``     | Accepts alphabetic characters   |
+-----------------+---------------------------------+
| ``{alphanum}``  | Accepts alphanumeric characters |
+-----------------+---------------------------------+
| ``{any}``       | Accepts any characters          |
+-----------------+---------------------------------+
| ``{hex}``       | Accepts a hexadecimal string    |
+-----------------+---------------------------------+
| ``{int}``       | Accepts a valid integer         |
+-----------------+---------------------------------+
| ``{md5}``       | Accepts a md5 hash              |
+-----------------+---------------------------------+
| ``{num}``       | Accepts a number                |
+-----------------+---------------------------------+
| ``{port}``      | Accepts a valid port number     |
+-----------------+---------------------------------+
| ``{scheme}``    | Accepts HTTP or HTTPS scheme    |
+-----------------+---------------------------------+
| ``{segment}``   | Accepts a URL path segment      |
+-----------------+---------------------------------+
| ``{slug}``      | Accepts a slug                  |
+-----------------+---------------------------------+
| ``{subdomain}`` | Accepts a subdomain             |
+-----------------+---------------------------------+
| ``{title}``     | Accepts a title format string   |
+-----------------+---------------------------------+
| ``{uuid}``      | Accepts a UUID                  |
+-----------------+---------------------------------+

.. code-block:: php

    $router->addPlaceholder('username', '([a-z\\d](?:[a-z\\d]|-(?=[a-z\\d])){0,16})');

Actions
-------

Closure or instance of Framework\Routing\RouteActions

Closure

.. code-block:: php

    $routes->get(
        '/posts/{int}',
        function(array $pathArgs, mixed ...$constructorArguments) {
            echo 'Post id is: ' . $pathArgs[0];
            var_dump($constructorArguments);
        }
    );

Framework\Routing\RouteActions

.. code-block:: php

    $routes->get('/posts/{int}', 'Posts::show/0');

.. code-block:: php

    use Framework\Routing\RouteActions;

    class Posts extends RouteActions
    {
        protected array $constructorArguments;

        public function __constructor(mixed ...$constructorArguments)
        {
            $this->constructorArguments = $constructorArguments;
        }

        public function show(int $id, string $category)
        {
            echo 'Category slug is: ' . $categoryId;
            echo 'Post id is: ' . $id;
            var_dump($this->constructorArguments);
        }
    }

.. code-block:: php

    class Posts extends RouteActions
    {
        public function show(int $id, string $category)
        {
            echo 'Category slug is: ' . $category;
            echo 'Post id is: ' . $id;
            var_dump($this->constructorArguments);
        }
    }

.. code-block:: php

    $routes->get('/categories/{slug}/posts/{int}/', 'Posts::show/1/0');

.. code-block:: php

    $routes->get('/categories/{slug}/posts/{int}/', 'Posts::show/*');

.. code-block:: diff

    @@ -3,4 +3,4 @@
         class Posts extends RouteActions
         {
    -       public function show(int $id, string $category)
    +       public function show(string $category, int $id)
            {
                echo 'Category slug is: ' . $category;
                echo 'Post id is: ' . $id;
                var_dump($this->constructorArguments);
            }
         }

.. code-block:: php

    $routes->get('/categories/{slug}/posts/{int}/', 'Posts::show');

Route Collection
----------------

The RouteCollection has several methods for creating Routes.

Most receive the name of the HTTP method to which the Route is assigned.

For example, the HTTP GET method has the ``get`` method. The POST, ``post``, etc.

.. code-block:: php

    $router->serve('http://domain.tld', function (RouteCollection $routes) {
  
        // Named route "collection-name.home"
        $routes->get('/', 'App\Controllers\Home::index', 'home');
        
        // Route with Closure instead of class method, and "collection-name.test" as name
        $routes->get('/test', function () {
            return 'Hello world!';
        }, 'test');
    
        // Different HTTP Methods using placeholders
        $routes->get('/user', 'App\Users::index');
        $routes->post('/user', 'App\Users::create');
        $routes->get('/user/{int}', 'App\Users::show/0');
        $routes->patch('/user/{username}', 'App\Users::update/0');
        $routes->put('/user/{int}', [\App\Users::class, 'replace']);
        $routes->delete('/user/{int}', 'App\Users::delete/*');

    }, 'collection-name');

Resources
^^^^^^^^^

.. code-block:: php

    $routes->resource('/users', 'App\Users', 'users');

Which will create 6 routes:

+-----------------+--------------+----------------------+---------------+
| HTTP Method     | Path         | Action               | Name          |
+=================+==============+======================+===============+
| **GET**         | /users       | App\Users::index/*   | users.index   |
+-----------------+--------------+----------------------+---------------+
| **POST**        | /users       | App\Users::create/*  | users.create  |
+-----------------+--------------+----------------------+---------------+
| **GET**         | /users/{int} | App\Users::show/*    | users.show    |
+-----------------+--------------+----------------------+---------------+
| **PATCH**       | /users/{int} | App\Users::update/*  | users.update  |
+-----------------+--------------+----------------------+---------------+
| **PUT**         | /users/{int} | App\Users::replace/* | users.replace |
+-----------------+--------------+----------------------+---------------+
| **DELETE**      | /users/{int} | App\Users::delete/*  | users.delete  |
+-----------------+--------------+----------------------+---------------+

Presenters
^^^^^^^^^^

Presenters create Routes to be used in a Web Browser User Interface.

.. code-block:: php

    $routes->presenter('/users', 'App\Users', 'users');

The routes are as follows:

+-----------------+---------------------+---------------------+--------------+
| HTTP Method     | Path                | Action              | Name         |
+=================+=====================+=====================+==============+
| **GET**         | /users              | App\Users::index/*  | users.index  |
+-----------------+---------------------+---------------------+--------------+
| **GET**         | /users/new          | App\Users::new/*    | users.new    |
+-----------------+---------------------+---------------------+--------------+
| **POST**        | /users              | App\Users::create/* | users.create |
+-----------------+---------------------+---------------------+--------------+
| **GET**         | /users/{int}        | App\Users::show/*   | users.show   |
+-----------------+---------------------+---------------------+--------------+
| **GET**         | /users/{int}/edit   | App\Users::edit/*   | users.edit   |
+-----------------+---------------------+---------------------+--------------+
| **POST**        | /users/{int}/update | App\Users::update/* | users.update |
+-----------------+---------------------+---------------------+--------------+
| **GET**         | /users/{int}/remove | App\Users::remove/* | users.remove |
+-----------------+---------------------+---------------------+--------------+
| **POST**        | /users/{int}/delete | App\Users::delete/* | users.delete |
+-----------------+---------------------+---------------------+--------------+

Groups
^^^^^^

Sometimes the route path can become repetitive and to simplify route creation 
is possible to group them with a base path.

.. code-block:: php

    $routes->group('/blog', [
        // Route for "/blog/"
        $routes->get('/', 'App\Blog\Posts::index'),
        // Route for "/blog/{title}"
        $routes->get('/{title}', 'App\Blog\Posts::show/0'),
    ]);

Grouping works on multiple layers. This also works:

.. code-block:: php

    $routes->group('/blog', [
        // Route for "/blog/"
        $routes->get('/', 'App\Blog\Posts::index'),
        $routes->group('/posts', [
            // Route for "/blog/posts/"
            $routes->get('/', 'App\Blog\Posts::index'),
            // Route for "/blog/posts/{title}"
            $routes->get('/{title}','App\Blog\Posts::show/0'),
        ]),
    ]);

Namespaces
^^^^^^^^^^

It is possible group route actions with the ``namespace`` method:

.. code-block:: php

    $routes->namespace('App\Controllers',[
        // Routes "/user" for App\Controllers\Users::index
        $routes->get('/user', 'Users::index'),
        $routes->namespace('Blog', [
            $routes->group('/blog', [
                // Routes "/blog/posts" for App\Controllers\Blog\Posts::index
                $routes->get('/posts', 'Posts::index'),
                // Routes "/blog/posts/{title}" for App\Controllers\Blog\Posts::show/0
                $routes->get('/posts/{title}', 'Posts::show/0'),
            ]),
        ]), 
    ]);

Route Not Found
^^^^^^^^^^^^^^^

Each RouteCollection can have its own custom Error 404 page.
It can be a Closure:

.. code-block:: php

    $routes->notFound(function () {
        return '<h1>Error 404</h1>';
    });

Or a class method string:

.. code-block:: php

    $routes->notFound('App\Controllers\Errors::notFound');

Routes
------

.. code-block:: php

    $route = $router->getNamedRoute('blog');
    echo $route->getUrl();

Route Actions
^^^^^^^^^^^^^

.. code-block:: php

    use Framework\HTTP\Request;
    use Framework\HTTP\Response;
    use Framework\Routing\RouteActions;
    use Framework\Routing\RouteCollection;
    use Framework\Routing\Router;

    class Admin extends RouteActions
    {
        protected Request $request;
        protected Response $response;
        protected Router $router;

        public function __construct(
            Request $request,
            Response $response,
            Router $router
        ) {
            $this->request = $request;
            $this->response = $response;
            $this->router = $router;
            session_start();
        }

        protected function beforeAction(string $method, array $arguments) : mixed
        {
            if( ! isset($_SESSION['user_id'])) {
                return $this->response->redirect(
                    $this->router->getNamedRoute('access.login')->getUrl()
                );
            }
            return null;
        }
        
        public function index()
        {
            echo 'Welcome, ' . $_SESSION['username'] . '!' ;
        }

        public function something($arg0, $arg1)
        {
            vard_dump($arg0, $arg1);
        }
    }

    $request = new Request();
    $response = new Response($request);
    $router = new Router($response);

    $router->serve(null, function(RouteCollection $routes) {
        $routes->get('admin', 'Admin::index');
        $routes->get('foo/other', 'Admin::something/1/0');
        $routes->get('login', 'Access\Login::index', 'access.login');
    });

    $route = $router->match();

    // Run the Route, passing the Request, Response and Router instances
    // to the RouteActions constructor,
    // or, if the action is as Closure; to its parameters
    $response = $route->run($request, $response, $router);

    $response->send();

Router
------

The router is where the RouteCollections are stored, and it decides which
Route will run according to the HTTP Request method and URL.

.. code-block:: php

    $router->serve('{scheme}://domain.tld', function (RouteCollection $routes) {
        // Routes ...
    })->serve('https://api.domain.tld', function (RouteCollection $routes) {
        // Routes ...
    }, 'api');

Default Route Not Found
^^^^^^^^^^^^^^^^^^^^^^^

If a RouteCollection does not have an Error 404 route set, the default router page
will be responded. It is customizable:

.. code-block:: php

    $router->setDefaultRouteNotFound('App\Errors::notFound');

Named Routes
^^^^^^^^^^^^

Routes in a collection can be named for easy route maintenance when a URL
changes its path.

Through the Router is possible take Routes by names:

.. code-block:: php

    $route = $router->getNamedRoute('api.users.followers');

Matched Route
^^^^^^^^^^^^^

After calling the ``match`` method, it is possible get the Route with ``getMatchedRoute``:

.. code-block:: php

    $route = $router->getMatchedRoute();

HTTP OPTIONS Method
^^^^^^^^^^^^^^^^^^^

The HTTP OPTIONS method serves to show which methods a particular resource 
makes available. With the routes defined, the server can answer automatically 
which methods are allowed.

.. code-block:: php

    $router->setAutoOptions();

HTTP Allowed Methods
^^^^^^^^^^^^^^^^^^^^

.. code-block:: php

    $router->setAutoMethods();
