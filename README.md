# Codeburner Router System

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![Build Status](https://img.shields.io/travis/codeburnerframework/router/master.svg)](https://travis-ci.org/codeburnerframework/router)
[![Code Coverage](https://scrutinizer-ci.com/g/codeburnerframework/routing/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/codeburnerframework/routing/?branch=master)
[![Quality Score](https://img.shields.io/scrutinizer/g/codeburnerframework/routing.svg)](https://scrutinizer-ci.com/g/codeburnerframework/routing)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/d96c4a67-982b-4e16-a24d-7b490bf11bc7/big.png)](https://insight.sensiolabs.com/projects/d96c4a67-982b-4e16-a24d-7b490bf11bc7)

A fast route dispatcher package that enables you to build blazing fast applications for the web. Thank's to [Nikita Popov's](https://github.com/nikic/) for [this post](https://nikic.github.io/2014/02/18/Fast-request-Router-using-regular-expressions.html).

##Instalation

Add `codeburner/Router` to your `composer.json` file.

```json
{
    "require": {
        "codeburner/router": "dev-master"
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
- [Collectors](#collectors)
    - [Controller Collector](#controller-collector)
    - [Resource Collector](#resource-collector)
        - [Restricting the Routes Created](#restricting-the-routes-created)
    - [Making Collectors](#making-collectors)
- [Dispatcher](#dispatcher)
    - [Basepath](#basepath)
    - [Dispatch Strategies](#dispatch-strategies)
    	- [URI Strategy](#uri-strategy)
    	- [Injector Strategy](#injector-strategy)
- [Exceptions](#exceptions)
	- [Not Found](#not-found)
	- [Method not Allowed](#method-not-allowed)
	- [Unauthorized](#unauthorized)

##Base Knowledge
First of all you need to understand some concepts of this project.

* __Collection__: This class will hold all the routes and compile then if necessary.
* __Dispatcher__: This will dispatch the Collection routes based on a given request http method and uri.
* __Collector__:  And this is an optional part, that will give an interface to insert routes into collection.
* __Strategies__: Is the form that the algoritm will react to given data.

##Basic Usage
After you have the classes ready to be instantiate, you only need to register the routes and call the dispatch method.

```php
use Codeburner\Router\Collection;
use Codeburner\Router\Collector;
use Codeburner\Router\Dispatcher;

$collection = new Collection;
$collector  = new Collector($collection);

// match for a GET request with "/" uri
$collector->get('/', function () {
	echo 'Hello World!';
});

$dispatcher = new Dispatcher('', $collector->getCollection());
$dispatcher->dispatch($_REQUEST['REQUEST_METHOD'], $_REQUEST['REQUEST_URI']);
```

##Routes
The Codeburner Router System have two types of routes, see below.

###Static Routes
The simplest way to define a route, all the `uri` is `static` and the action have no parameters. This implementation will `echo` `I'm on dashboard` if user request the `yourdomain/dashboard` url.

```php
$collector->get('/dashboard', function () {
	echo 'I\'m on dashboard!';
});
```

###Dinamic Routes
In the other hand the dinamic routes have some variables on the `uri`, this variables will be used as the action parameters. This definition will match if user request url like `yourdomain/account/alex` and will `echo` `Hello alex!`.

```php
$collector->get('/account/{name}', function ($name) {
	echo "Hello $name!";
});
```

####Segments Constraints
You can use the constraints to enforce a format for a dynamic segment:

```php
$collector->get('/photos/{id:[A-Z]\d+}', 'PhotosController#show');
```

This route would match paths such as `/photos/A123456`, but not `/photos/897`.

Constraints takes regular expressions with the restriction that regex must not use capturing groups. For example `{lang:(en|de)}` is not a valid placeholder, because `()` is a capturing group. Instead you can use either `{lang:en|de}` or `{lang:(?:en|de)}`.

###Optional Segments
For optinal segments in your routes use the `[` and `]` statement to embrace the optional part. Optional segments must only be in the end of pattern and close all opened `[` with `]`. For example:

```php
$collector->get('/users/{id:\d+}[/{name}]', function ($id, $name = 'unknown') {
    echo "Hello $name your id is $id.";
});
```

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

$collector->get('/heisenberg/{name}', 'HeisenbergController#sayMyName');
```

You could change the delimiter by setting it like this:

```php
$collector->getCollection()->setDelimiter('@');
// so the new delimiter will be @
$collector->get('/heisenberg/{name}', 'HeisenbergController@sayMyName');
```

####Array Mode
Or you could pass an array with two elements, the first is the object and the second is the name of method.

```php
class HeisenbergController {
    public function sayMyName($name) {
        echo "Hello $name!";
    }
}

$collector->get('/heisenberg/{name}', ['HeisenbergController', 'sayMyName']);
```

Sometimes you need to call a specific method for a especific route, you don't need to register lots of routes for that, only register a global route like this:

```php
class HeisenbergController {
	public function cook($number) {
		echo "cooking $number cristals...";
	}
}

$collector->get('/{person}/{action}/{number}', '{person}Controller#{action}');
```

This should match `/Heisenberg/cook/1000000` for example, and execute the `HeisenbergController#cook` action with parameter 1000000.

###Anonymous Functions/Closures

The simpliest way to define an action, you only need to pass a closure as parameter.

```php
$dispatcher->post('/{entitie}/{id}/update', function ($entitie, $id) {
	// execute the action logic...
});
```

###Named Functions
The same way of [Anonymous Functions/Closures](#anonymous-functionsclosures) but you define a named function and pass his name as parameter.

```php
function action1() {
	// execute the action logic...
}

$collector->get('/', 'action1');
```

##Request Methods
The router has convenience methods for setting routes that will respond differently depending on the HTTP request method by default. Each of the above routes will respond to the same URI but will invoke a different action based on the HTTP request method. __But this is only acessible on the Collector__, if you wanna kick off the collector and use only the base of package you can inject routes direct into the Collecion.

```php
// Using the Collector...

$collector->get('/', 'controller#action');
$collector->post('/', 'controller#action');
$collector->put('/', 'controller#action');
$collector->patch('/', 'controller#action');
$collector->delete('/', 'controller#action');
$collector->any('/', 'controller#action'); // will match in any request method
$collector->match(['get', 'post'], '/', 'controller#action'); // will match in GET and POST requests
$collector->except(['put', 'delete'], '/', 'controller#action'); // will match in any request method but put and delete.

// Injecting Directly into Collection...

$collection->set('GET', '/', 'controller#action');
```

> **NOTE:** Routing both GET and POST requests to a single action has security implications. In general, you should avoid routing all verbs to an action unless you have a good reason to.

##Collectors
Collectors give an simple interface between you and the route collection, in then you can use all the [request methods](#request-methods) by default, and yea by default you can collect controllers and make resources with a single line of code.

###Controller Collector
If you don't wanna to define each route to a controller, you can use this helper. First you should follow some rules to get it to work.
- Methods that will be registered __must__ begin with the correspondent HTTP method, like GET, POST, PUT, DELETE.
- Camelcased methods will be converted to uri, each word will receive a slash `/` by prefix, like SomeMethod -> some/method.
And that's all! Now you need only to tell the collector to craw the controller.

```php
class UserController {
    public function getName() {} // will match to GET /user/name
    public function getSomeAttribute($id) {} // will match to GET /some/attribute/{id}
}

$collector->controller('UserController');
```

As you see the paths are prefixed with the controller name, it's a default, you can disable this passing a second argument to controller collector method with a false boolean, like above:

```php
$collector->controller('UserController', false);
```

You can define a more specific constraint with PHPDoc `@param` and the Match definition in the param comments:

```php
class UserController {
    /**
     * this will match to GET /user/blog/post/{id:\d} like /user/blog/post/123456789
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

###Resource Collector
Some times you need to be more RESTFul, with the resources you can, they will define 7 routes for you. For example if you give a single entry in the collector such as:

```php
$collector->resource('PhotosController');
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

####Restricting the Routes Created
By default all the 7 routes will be created for a resource controller, but you can use the `only` and the `except` options to fine-tune this behavior. The `only` option tells to create only the specified routes.

```php
$collector->resource('PhotosController', ['only' => ['index', 'show']]);
```

Now, a GET request to `/photos` would suceed, but a POST request to `/photos` (Wich would ordinarily be routed to the create action) will fail.

The `except` option specifies a route or list of routes that should __not__ create:

```php
$collector->resource('PhotosController', ['except' => ['destroy']]);
```

In this case, all the normal routes except the route for destroy (a DELETE request to `/photos/{id}`) will be created.

> **TIP:** If your application has many RESTFul routes, using `only` and `except` to generate only the routes that actually need can cut down on memory use and speed up the routing process.
 
###Making Collectors
If you wanna a more specific way to define routes, you can make a class and register into the collector, the only requirement is to implement the `Codeburner\Router\Strategies\Collector\StrategyInterface`. For example if you wanna a more literal method to register routes like for example, match a `GET` request for a `/path` into `myController#myMethod`:

```php
$collector->literal('GET /path to myMethod in myController');
```

You can do this by yourself creating a new collector.

```php
use Codeburner\Router\Strategies\Collector\StrategyInterface;
use Codeburner\Router\Collector;

class LiteralCollector implements StrategyInterface {
    public function literal($string) {
        $string = explode(' ', $string);

        $this->collector->match($string[0], $string[1], "{$string[4]}#{$string[3]}");
    }

    public function register(Collector $collector) {
        $collector->setMethod('literal', [$this, 'literal']);
    }
}
```

And register it into the Collector.

```php
$collector->setCollector(new LiteralCollector);
```

>**NOTE:** The Controller and Resource colletors don't implement the strategy interface, they are manually registered into the collector. So if you wanna to extend they, you __must__ implement the `Codeburner\Router\Strategies\Collector\StrategyInterface`

##Dispatcher
The dispatcher is one of the main components of this package, the dispatcher will find a route that match the given HTTP method and URI, and use one especific strategy to call the matched route action.

###Basepath
An important point of dispatcher is that it can remove the basepath prefix from the routes that you wanna match, for this the first parameter of the dispatcher should be a string with the basepath.

###Dispatch Strategies
Sometimes a especific action must receive a especific information, like the route parameters, or a dependency from the container. You can define this with the second parameter of the dispatcher that must be an implementation of `Codeburner\Router\Strategies\Dispatcher\StrategyInterface`.

####URI Strategy
This dispatch strategy will give your actions all the parameters of route, and this is the __default__ strategy used.

```php
$collector->get('/user/{id}', function ($id) {
    // ...    
});
```

####Injector Strategy
This strategy will use the [Codeburner Container](https://github.com/codeburnerframework/container) to inject all the dependencies of your action and after that the URI variables will be passed.

```php
$collector->get('/user/{id}', function (\Monolog\Logger $logger, $id) {
    // ...
})
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
$collector->get('/foo', 'controller@action');

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

###Unauthorized
A filter blocking the request passage `Codeburner\Router\Exceptions\UnauthorizedException`
