# Codeburner Router System

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![Build Status](https://img.shields.io/travis/codeburnerframework/routing/master.svg)](https://travis-ci.org/codeburnerframework/routing)
[![Code Coverage](https://scrutinizer-ci.com/g/codeburnerframework/routing/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/codeburnerframework/routing/?branch=master)
[![Quality Score](https://img.shields.io/scrutinizer/g/codeburnerframework/routing.svg)](https://scrutinizer-ci.com/g/codeburnerframework/routing)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/d96c4a67-982b-4e16-a24d-7b490bf11bc7/big.png)](https://insight.sensiolabs.com/projects/d96c4a67-982b-4e16-a24d-7b490bf11bc7)

A fast route dispatcher package that enables you to build blazing fast applications for the web. Thank's to [Nikita Popov's](https://github.com/nikic/) for [this post](https://nikic.github.io/2014/02/18/Fast-request-Router-using-regular-expressions.html).

##Instalation

###Manual
[Download the zip](https://github.com/codeburnerframework/Router/archive/master.zip) and extract into your directory, then include the `src/dispatcher.php` file, and that's it!.

###Composer
Add `codeburner/Router` to your `composer.json` file.

```json
{
    "require": {
        "codeburner/Router": "dev-master"
    }
}
```

Don't forget to install or update the composer and include the `vendor/autoload.php` file.

##Usage

- [Basic Usage](#basic-usage)
- [Routes](#routes)
	- [Static Routes](#static-routes)
	- [Dinamic Routes](#dinamic-routes)
		- [Route Pattern](#route-pattern)
- [Action Types](#action-types)
    - [Class Methods](#class-methods)
    	- [String Mode](#string-mode)
    		- [Static Class Methods](#static-class-methods)
	- [Array Mode](#array-mode)
    - [Anonymous Functions/Closures](#anonymous-functionsclosures)
    - [Named Functions](#name-functions)
- [Request Methods](#request-methods)
- [Exceptions](#exceptions)
	- [Not Found](#not-found)
	- [Method not Allowed](#method-not-allowed)
	- [Unauthorized](#unauthorized)

###Basic Usage
After you have the classes ready to be instantiate, you only need to register the routes and call the dispatch method.

```php
use Codeburner\Router\Dispatcher;

$dispatcher = new Dispatcher;

// match for a GET request with "/" uri
$dispatcher->get('/', function () {
	echo 'Hello World!';
});

$dispatcher->dispatch($_REQUEST['REQUEST_METHOD'], $_REQUEST['REQUEST_URI']);
```
###Routes
The Codeburner Router System have two types of routes, see below.

####Static Routes
The simplest way to define a route, all the path or `uri` is `static` and the action have no parameters.

```php
$dispatcher->get('/dashboard', function () {
	echo 'I\'m on dashboard!';
});
```

####Dinamic Routes
In the other hand the dinamic routes have some variables on the `uri`, this variables will be used as the action parameters.

```php
$dispatcher->get('/account/{name}', function ($name) {
	echo "Hello $name!";
});
```

#####Route Pattern
By default a route pattern syntax is used where `{foo}` specified a placeholder with name foo and matching the string `[^/]+`. To adjust the pattern the placeholder matches, you can specify a custom pattern by writing `{foo:[0-9]+}`. A custom pattern for a route placeholder must not use capturing groups. For example `{lang:(en|de)}` is not a valid placeholder, because `()` is a capturing group. Instead you can use either `{lang:en|de}` or `{lang:(?:en|de)}`.

###Action Types
Actions are what will be executed if some route match the request, there are three ways to define this actions, see below.

####Class Methods
In one MVC application you may wish to route to a controller. To call a controller method you have some options see below:

#####String Mode
You could call on string mode, that will explode the string in the `#` delimiter and create a new instance of the class.
```php
class MyController {
	public function myMethod($name) {
		echo "Hello $name!";
	}
}

$dispatcher->get('/account/{name}', 'MyController#myMethod');
```
You could change the delimiter by setting it like this:
```php
$dispatcher->getStrategy()->setDelimiter('@');
// so the new delimiter will be @
$disptcher->get('/test', 'someController@someMethod');
```

#####Array Mode
Or you could pass an array with two elements, the first is the object and the second is the name of method.
```php
class MyController {
	public function myMethod($name) {
		echo "Hello $name!";
	}
}

$dispatcher->get('/account/{name}', ['MyController', 'myMethod']);
```

Sometimes you need to call a specific method for a especific route, you don't need to register lots of routes for that, only register a global route like this:

```php
class MyController {
	public function method1($id) {
		echo "id: $id";
	}
}

$dispatcher->get('/{entitie}/{id}', 'MyController#{entitie}');
```
This will match `/method1/1` for example, and execute the `MyController#method1` action.


####Anonymous Functions/Closures

The simpliest way to define an action, you only need to pass a closure as parameter.

```php
$dispatcher->post('/{entitie}/{id}/update', function ($entitie, $id) {
	// execute the action logic...
});
```

####Named Functions
The same way of [Anonymous Functions/Closures](#anonymous-functionsclosures) but you define a named function and pass his name as parameter.

```php
function action1() {
	// execute the action logic...
}

$dispatcher->get('/', 'action1');
```

###Request Methods
The router has convenience methods for setting routes that will respond differently depending on the HTTP request method.

```php
$dispatcher->get('/', 'controller#action');
$dispatcher->post('/', 'controller#action');
$dispatcher->put('/', 'controller#action');
$dispatcher->patch('/', 'controller#action');
$dispatcher->delete('/', 'controller#action');
$dispatcher->head('/', 'controller#action');
$dispatcher->options('/', 'controller#action');
$dispatcher->any('/', 'controller#action'); // will match in any request method
$dispatcher->map(['get', 'post'], '/', 'controller#action'); // will match in GET and POST requests
```
Each of the above routes will respond to the same URI but will invoke a different action based on the HTTP request method.

###Exceptions
Exceptions will not be found if you have used the manual installation method, you need to include the especific file to have then throwed properly.
####Not Found
Route not found exception `Codeburner\Router\Exceptions\NotFoundException`
####Method not Allowed
Route method is wrong `Codeburner\Router\Exceptions\MethodNotAllowedException`
####Unauthorized
A filter blocking the request passage `Codeburner\Router\Exceptions\UnauthorizedException`
