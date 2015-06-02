# Codeburner Routing System
A fast route dispatcher package that enables you to build blazing fast applications for the web. Thank's to [Nikita Popov's](https://github.com/nikic/) for [this post](https://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html).

##Instalation

###Manual
[Download the zip](https://github.com/codeburnerframework/routing/archive/master.zip) and extract into your directory, then include the `src/bootstrap.php` file, and that's it!.

###Composer
Add `codeburner/routing` to your `composer.json` file.

```json
{
    "require": {
        "codeburner/routing": "dev-master"
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
    - [Anonymous Functions/Closures](#anonymous-functionsclosures)
    - [Named Functions](#name-functions)
- [Request Methods](#request-methods)
- [Filtering Routes](#filtering-routes)
- [Grouping Routes](#grouping-routes)
- [Named Routes](#named-routes)
- [Exceptions](#exceptions)
	- [Not Found](#not-found)
	- [Method not Allowed](#method-not-allowed)
	- [Unauthorized](#unauthorized)

###Basic Usage
After you have the classes ready to be instantiate, you only need to register the routes and call the dispatch method.

```php
use Codeburner\Routing\Dispatcher;

$dispatcher = new Dispatcher;

// match for a GET request with "/" uri
$dispatcher->get('/', function () {
	echo 'Hello World!';
});

$dispatcher->dispatch($_REQUEST['REQUEST_METHOD'], $_REQUEST['REQUEST_URI']);
```
###Routes
The Codeburner Routing System have two types of routes, see below.

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
In one MVC application you may wish to route to a controller.

```php
class MyController
{

	public function myMethod($name)
	{
		echo "Hello $name!";
	}

}

$dispatcher->get('/account/{name}', 'MyController@myMethod');
```

Sometimes you need to call a specific method for a especific route, you don't need to register lots of routes for that, only register a global route like this:

```php
class MyController
{

	public function method1($id)
	{
		echo "id: $id";
	}

}

$dispatcher->get('/{entitie}/{id}', 'MyController@{entitie}');
```
This will match `/method1/1` for example, and execute the `MyController@method1` action.

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
function action1()
{
	// execute the action logic...
}

$dispatcher->get('/', 'action1');
```

###Request Methods
The router has convenience methods for setting routes that will respond differently depending on the HTTP request method.

```php
$dispatcher->get('/', 'controller@action');
$dispatcher->post('/', 'controller@action');
$dispatcher->put('/', 'controller@action');
$dispatcher->patch('/', 'controller@action');
$dispatcher->delete('/', 'controller@action');
$dispatcher->head('/', 'controller@action');
$dispatcher->options('/', 'controller@action');
$dispatcher->any('/', 'controller@action'); // will match in any request method
$dispatcher->map(['get', 'post'], '/', 'controller@action'); // will match in GET and POST requests
```
Each of the above routes will respond to the same URI but will invoke a different action based on the HTTP request method.

###Filtering Routes
You may wish some routes to be accessible only for some users or request, you can define this using the filter system.

```php

use Codeburner\Routing\RouteFilterInterface;

class AuthFilter implements RouteFilterInterface
{

	public function handle()
	{
		if (!empty($_SESSION)) {
			return true;
		}

		return false;
	}

}

$dispatcher->filter('auth', new AuthFilter);
$dispatcher->get('/dashboard', 'DashboardController@home', 'auth'); // Will only match if the $_SESSION exists.
```
You can define so many filter as you wish, define than as array or as string separating then with `|`.

###Grouping Routes
Commonly you have routes that have same filters, the same prefix, same controller namespace... You might merge then in one group.

```php

// These routes inside the anonymous function will be prefixed with "/user"
$dispatcher->group('user', function ($dispatcher) {
	$dispatcher->get('/', 'Path/to/Controller@index');
	$dispatcher->get('/config', 'Path/to/Controller@config');
});

// And these routes will be prefixed with "dashboard", will have 2 filters, "auth" and "admin" and the controllers will be prefixed with "Path/To/Controllers/Folder"
$dispatcher->group(['prefix' => 'dashboard', 'filters' => 'auth|admin', 'namespace' => 'Path/To/Controllers/Folder'], function ($dispacher) {
	$dispatcher->get('/', 'DashboardController@index');
	$dispatcher->get('/config', 'DashboardController@config');
});
```
###Named Routes
You can define a name for the routes, for the cases that you need to generate a dinamic URi for example:

```php
$dispatcher->get('/dashboard', 'dashboardcontroller@home', [], 'dashboard.home');
$dispatcher->get('/dashboard/{page}', 'dashboardcontroller@page', [], 'dashboard.page')
```
```html
<a href="<?php echo $dispatcher->uri('dashboard.home') ?>" alt="Dashboard">home</a>
<a href="<?php echo $dispatcher->uri('dashboard.page', ['thePageParameter']) ?>" alt="Some Page">some page</a>
```

the first link will generate a `/dashboard` route and the second will generate `/dashboard/thePageParameter`.

###Exceptions
####Not Found
Route not found exception `Codeburner\Routing\Exceptions\NotFoundException`
####Method not Allowed
Route method is wrong `Codeburner\Routing\Exceptions\MethodNotAllowedException`
####Unauthorized
A filter blocking the request passage `Codeburner\Routing\Exceptions\UnauthorizedException`
