# Codeburner Router System

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![Build Status](https://img.shields.io/travis/codeburnerframework/router/master.svg)](https://travis-ci.org/codeburnerframework/router)
[![Code Coverage](https://scrutinizer-ci.com/g/codeburnerframework/routing/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/codeburnerframework/routing/?branch=master)
[![Quality Score](https://img.shields.io/scrutinizer/g/codeburnerframework/routing.svg)](https://scrutinizer-ci.com/g/codeburnerframework/routing)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/d96c4a67-982b-4e16-a24d-7b490bf11bc7/big.png)](https://insight.sensiolabs.com/projects/d96c4a67-982b-4e16-a24d-7b490bf11bc7)

An blazing fast PHP router system. Thank's to [Nikita Popov's](https://github.com/nikic/) for [this post](https://nikic.github.io/2014/02/18/Fast-request-Router-using-regular-expressions.html).

##Instalation

Add `codeburner/Router` to your `composer.json` file.

```json
{
    "require": {
        "codeburner/router": "1.*"
    }
}
```
or via cli
```
$ composer require codeburner/router
```

Don't forget to install or update the composer and include the `vendor/autoload.php` file.

##Table of Content

- [Basic Usage](#basic-usage)
- [Routes](#routes)
    - [Static Routes](#static-routes)
    - [Dinamic Routes](#dinamic-routes)
        - [Segments Constraints](#segments-constraints)
    - [Optional Segments](#optional-segments)
- [Action Types](#action-types)
    - [Class Methods](#class-methods)
        - [String Mode](#string-mode)
        - [Array Mode](#array-mode)
    - [Anonymous Functions/Closures](#anonymous-functionsclosures)
    - [Named Functions](#name-functions)
- [Request Methods](#request-methods)
- [Controller Collector](#controller-collector)
- [Resource Collector](#resource-collector)
    - [Restricting the Routes Created](#restricting-the-routes-created)
- [Namespacing Routes](#namespacing-routes)
- [Dispatcher](#dispatcher)
    - [Basepath](#basepath)
    - [Dispatch Strategies](#dispatch-strategies)
        - [URI Strategy](#uri-strategy)
- [Exceptions](#exceptions)
    - [Not Found](#not-found)
    - [Method not Allowed](#method-not-allowed)
- [Benchmark](#benchmark)

##Base Knowledge
First of all you need to understand some concepts of this project.

* __Mapper__: This class will hold all the routes and compile then if necessary.
* __Dispatcher__: This will dispatch the Collection routes based on a given request http method and uri.
* __Strategies__: Is the form that the algoritm will dispatch a given route.

##Basic Usage
After you have the classes ready to be instantiate, you only need to register the routes and call the dispatch method.

```php
use Codeburner\Router\Mapper;
use Codeburner\Router\Dispatcher;

$mapper = new Mapper;
$dispatcher = new Dispatcher($mapper);

// match for a GET request with "/" uri
$mapper->get('/', function () {
    echo 'Hello World!';
});

// echoes Hello World! if the REQUEST_METHOD is GET and the REQUEST_URI is /
$dispatcher->dispatch($_REQUEST['REQUEST_METHOD'], $_REQUEST['REQUEST_URI']);
```

##Routes
The Codeburner Router System have two types of routes, see below.

###Static Routes
The simplest way to define a route, all the `uri` is `static` and the action have no parameters. This implementation will `echo` `I'm on dashboard` if user request the `yourdomain.com/dashboard` url with `get` http method.

```php
$mapper->get('/dashboard', function () {
    echo 'I\'m on dashboard!';
});
```

###Dinamic Routes
In the other hand the dinamic routes have some variables on the `uri`, this variables will be used as the action parameters. This definition will match if user request url like `yourdomain.com/account/alex` and will `echo` `Hello alex!`.

```php
$mapper->get('/account/{name}', function ($name) {
    echo "Hello $name!";
});
```

####Segments Constraints
You can use the constraints to enforce a format for a dynamic segment:

```php
$mapper->get('/photos/{id:[A-Z]\d+}', 'PhotosController#show');
```

This route would match paths such as `/photos/A123456`, but not `/photos/897`.

Constraints takes regular expressions with the restriction that regex must not use capturing groups. For example `{lang:(en|de)}` is not a valid placeholder, because `()` is a capturing group. Instead you can use either `{lang:en|de}` or `{lang:(?:en|de)}`.

###Optional Segments
For optinal segments in your routes use the `[` and `]` statement to embrace the optional part. Optional segments must only be in the end of pattern and close all opened `[` with `]`. For example:

```php
$mapper->get('/users/{id:\d+}[/{name}]', function ($id, $name = 'unknown') {
    echo "Hello $name your id is $id.";
});
```
This will print `Hello alex your id is 7` for a `/users/7/alex` uri and `Hello unknown your id is 7` for a `/users/7` uri.

##Action Types
Actions are what will be executed if some route match the request, there are three ways to define this actions, see below.

###Class Methods
In one MVC application you may wish to route to a controller. To call a controller method you have some options see below:

####String Mode
You could call on string mode, that will explode the string in the `#` delimiter by default and create a new instance of the class.

```php
class HeisenbergController {
    public function sayMyName($name) {
        echo "Hello $name!";
    }
}

$mapper->get('/heisenberg/{name}', 'HeisenbergController#sayMyName');
```

You could change the delimiter by setting it like this:

```php
$mapper->setActionDelimiter('@');
// so the new delimiter will be @
$mapper->get('/heisenberg/{name}', 'HeisenbergController@sayMyName');
```

####Array Mode
Or you could pass an array with two elements, the first is the object and the second is the name of method.

```php
// ...
$mapper->get('/heisenberg/{name}', [HeisenbergController::class, 'sayMyName']);
```

Sometimes you need to call a specific method for a especific route, you don't need to register lots of routes for that, only register a global route like this:

```php
class HeisenbergController {
    public function cook($number) {
        echo "cooking $number cristals...";
    }
}

$mapper->get('/{person}/{action}/{number}', '{person}Controller#{action}');
```

This should match `/Heisenberg/cook/1000000` for example, and execute the `HeisenbergController#cook` action with parameter 1000000.

###Anonymous Functions/Closures

The simpliest way to define an action, you only need to pass a closure as parameter.

```php
$mapper->post('/{entitie}/{id}/update', function ($entitie, $id) {
    // execute the action logic...
});
```

###Named Functions
The same way of [Anonymous Functions/Closures](#anonymous-functionsclosures) but you define a named function and pass his name as parameter.

```php
function action1() {
    // execute the action logic...
}

$mapper->get('/', 'action1');
```

##Request Methods
The router has convenience methods for setting routes that will respond differently depending on the HTTP request method by default. Each of the above routes will respond to the same URI but will invoke a different action based on the HTTP request method.

```php
$mapper->get('/', 'controller#action');
$mapper->post('/', 'controller#action');
$mapper->put('/', 'controller#action');
$mapper->patch('/', 'controller#action');
$mapper->delete('/', 'controller#action');
$mapper->any('/', 'controller#action'); // will match in any request method
$mapper->match(['get', 'post'], '/', 'controller#action'); // will match in GET and POST requests
$mapper->except(['put', 'delete'], '/', 'controller#action'); // will match in any request method but put and delete.
```

> **NOTE:** Routing both GET and POST requests to a single action has security implications. In general, you should avoid routing all verbs to an action unless you have a good reason to.

##Controller Collector
If you don't wanna to define each route to a controller, you can use this helper. First you should follow some rules to get it to work.
- Methods that will be registered __must__ begin with the correspondent HTTP method, like GET, POST, PUT, DELETE.
- Camelcased methods will be converted to uri, each word will receive a slash `/` by prefix, like SomeMethod -> some/method.
And that's all! Now you need only to tell the collector to craw the controller.

```php
class UserController {
    public function getName() {} // will match to GET /user/name
    public function getSomeAttribute($id) {} // will match to GET /user/some/attribute/{id}
}

$mapper->controller(UserController::class);
```

As you see the paths are prefixed with the controller name, it's a default, you can disable this passing a second argument to controller collector method with a false boolean, like above:

```php
$mapper->controller(UserController::class, false);
```

You can define a more specific constraint with [PHPDoc](http://www.phpdoc.org/) `@param` and the Match definition in the param comments:

```php
class UserController {
    /**
     * this will match to GET /user/blog/post/{id:\d+} like /user/blog/post/123456789
     *
     * @param integer $id
     */
    public function getBlogPost($id) {}
    
    /**
     * This will match to GET /user/blog/comment/{id:(\d{5})} like /user/blog/comment/98765
     *
     * @param integer $id Match (\d{5}) rest of the comment...
     */
    public function getBlogComment($id) {}
}
```

##Resource Collector
Some times you need to be more RESTFul, with the resources you can, they will define 7 routes for you. For example if you give a single entry in the collector such as:

```php
$mapper->resource(PhotosController::class);
```

The collector will create this routes for the `PhotosController` 

Method|Path|Controller#Action|Used For
---------|----|-----------------|--------
GET | /photos | PhotosController#index | Display a list of all photos
GET | /photos/make | PhotosController#make | Return an HTML form for creating a new photo
POST | /photos | PhotosController#create | Create a new photo
GET | /photos/{id} | PhotosController#show | Display a specific photo
GET | /photos/{id}/edit | PhotosController#edit | Return an HTML form for editing a photo
PUT | /photos/{id} | PhotosController#update | Update a specific photo
DELETE | /photos/{id} | PhotosController#destroy | Delete a specific photo

> **NOTE:** Because the router uses the HTTP verb and URL to match inbound requests, four URLs map to seven different actions.

> **NOTE:** Like Rails routes are matched in order they are specified, so if you have a resource `photos` above a get `photos/poll` the `show` action's route for the resources line will be matched before the get line. To fix this, move the get line __above__ the resources line so that it is matched first.

###Restricting the Routes Created
By default all the 7 routes will be created for a resource controller, but you can use the `only` and the `except` options to fine-tune this behavior. The `only` option tells to create only the specified routes.

```php
$mapper->resource(PhotosController::class, ['only' => ['index', 'show']]);
```

Now, a GET request to `/photos` would suceed, but a POST request to `/photos` (Wich would ordinarily be routed to the create action) will fail.

The `except` option specifies a route or list of routes that should __not__ create:

```php
$mapper->resource(PhotosController::class, ['except' => ['destroy']]);
```

In this case, all the normal routes except the route for destroy (a DELETE request to `/photos/{id}`) will be created.

> **TIP:** If your application has many RESTFul routes, using `only` and `except` to generate only the routes that actually need can cut down on memory use and speed up the routing process.

##Namespacing Routes
For working with [namespaces](http://php.net/manual/en/language.namespaces.php) the mapper uses the buildin PHP namespace syntax:

```php
namespace App\Controllers
{
    $mapper->resource(CategoryController::class);
    $mapper->controller(DashboardController::class);
    $mapper->get('/js/{file}', [AssetsController::class, 'getJsFile']);
}
```
Using the PHP 5.5 `::class` syntax you will get the full class name with a clean syntax.

##Dispatcher
The dispatcher will find a route that match the given HTTP method and URI, and use one especific strategy to call the matched route action.

###Basepath
An important point of dispatcher is that it can remove the basepath prefix from the routes that you wanna match, for this the first parameter of the dispatcher should be a string with the basepath.

###Dispatch Strategies
Sometimes a especific action must receive a especific information, like the route parameters, or a dependency from the container. You can define this with the second parameter of the dispatcher that must be an implementation of `Codeburner\Router\StrategyInterface`.

####URI Strategy
This dispatch strategy will give your actions all the parameters of route, and this is the __default__ strategy used.

```php
$mapper->get('/user/{id}', function ($id) {
    // ...
});
```

##Exceptions

###Not Found
Route not found exception `Codeburner\Router\Exceptions\NotFoundException`

```php
try {
    $dispatcher->dispatch('post', 'foo');
} catch (Codeburner\Router\Exceptions\NotFoundException $e) {
    // show some not found page.
    die("Request failed for method {$e->requested_method} and uri {$e->requested_uri}");
}
```

###Method not Allowed
Route method is wrong `Codeburner\Router\Exceptions\MethodNotAllowedException`

```php
$mapper->get('/foo', 'controller@action');

try {
    $dispatcher->dispatch('post', '/foo');
} catch (Codeburner\Router\Exceptions\MethodNotAllowedException $e) {
    // You can for example, redirect to the correct request.
    // this if verify if the requested route can serve get requests.
    if ($e->can('get')) {
        // if so, dispatch into get method.
        $dispatcher->dispatch('get', $e->requested_uri);
    }
}
```

> **NOTE:** The HTTP specification requires that a `405 Method Not Allowed` response include the
`Allow:` header to detail available methods for the requested resource. For this you can get a
string with a processed allowed methods by using the `allowed` method of this exception.

##Benchmark
Codeburner Router system was compared to [Nikita's fast route](https://github.com/nikic/fastroute) and the results on an Core i5 3230m, Ram 8GB, SSD Kingston SH103S3120G with ubuntu 15.04. The tests have showed theses results:

package   |Matching|n routes|n args|map time|match time|usage time
----------|--------|--------|------|--------|----------|----------
fastroute |last    |100     |1..10 |0.0165  |0.00075   |0.01728796
codeburner|last    |100     |1..10 |0.0066  |0.00024   |0.00685691
fastroute |first   |100     |1..10 |0.0135  |0.00042   |0.01397585
codeburner|first   |100     |1..10 |0.0057  |0.00016   |0.00588297
fastroute |last    |100     |9     |0.0149  |0.00050   |0.01556897
codeburner|last    |100     |9     |0.0070  |0.00068   |0.00763726
fastroute |first   |100     |9     |0.0168  |0.00047   |0.01728487
codeburner|first   |100     |9     |0.0080  |0.00048   |0.00856208

Where map time is the time cost for registering all the routes, match time is the cost for find the specific route, and usage time is the sum of map and match plus the time to execute the callback of the matched route. The codeburner results have an __average of 55% times faster__ usage cost than the fastroute. Note that this is average values of a simple PHP script that can be found [here](https://gist.github.com/alexrohleder96/c6ba88234e51f301a1ab)
