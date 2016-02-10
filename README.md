# Codeburner Router

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![Build Status](https://travis-ci.org/codeburnerframework/router.svg?branch=master)](https://travis-ci.org/codeburnerframework/router)
[![Code Coverage](https://scrutinizer-ci.com/g/codeburnerframework/routing/badges/coverage.png?b=dev)](https://scrutinizer-ci.com/g/codeburnerframework/routing/?branch=dev)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/codeburnerframework/routing/badges/quality-score.png?b=dev)](https://scrutinizer-ci.com/g/codeburnerframework/routing/?branch=dev)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/d96c4a67-982b-4e16-a24d-7b490bf11bc7/big.png)](https://insight.sensiolabs.com/projects/d96c4a67-982b-4e16-a24d-7b490bf11bc7)

An blazing fast PHP router system with amazing features and abstraction.
Thank's to [Nikita Popov's](https://github.com/nikic/) for motivate me with [this post](https://nikic.github.io/2014/02/18/Fast-request-Router-using-regular-expressions.html).

## Instalation

Add `codeburner/router` to your `composer.json` file, and update or install composer dependencies.

```json
{
    "require": {
        "codeburner/router": "2.*"
    }
}
```

or via CLI:

```bash
$ composer require codeburner/router --save
```

## Table of Content

- [Introduction](#introduction)
    - [Performance](#performance)
    - [Connecting URLs to Code](#connecting-urls-to-code)
    - [Usage Example](#usage-example)
- [Routes](#routes)
    - [Patterns](#patterns)
        - [Constraints](#constraints)
            - [Wildcards](#wildcards)
        - [Optional Segments](#optional-segments)
        - [Unicode Characters](#unicode-characters)
    - [Actions](#actions)
        - [Strategies](#strategies)
            - [Enhancers](#enhancers)
    - [Defaults and Metadata](#defaults-and-metadata)
- [Collector](#collector)
    - [Supported Methods](#supported-methods)
    - [Groups](#groups)
        - [Namespaces](#namespaces)
        - [Prefixes](#prefixes)
        - [Strategies](#strategies)
        - [Constraints](#constraints)
    - [Controllers](#controllers)
        - [Changing Action Join](#changing-action-join)
        - [Prefixing Controllers or Ignoring Auto-Prefix](#prefixing-controllers-or-ignoring-auto-prefix)
        - [Defining Multiple Controllers at Same Time](#defining-multiple-controllers-at-same-time)
        - [Annotated Information](#annotated-information)
    - [Resources](#resources)
        - [Prefixing Resources or Ignoring Auto-Prefix](#prefixing-resources-or-ignoring-auto-prefix)
        - [Defining Multiple Resources at Same Time](#defining-multiple-resources-at-same-time)
        - [Restricting Created Routes](#restricting-created-routes)
        - [Nested Resources](#nested-resources)
            - [Limits to Nesting](#limits-to-nesting)
            - [Shallowed Resources](#shallowed-resources)
            - [Adding More RESTful Actions](#adding-more-restful-actions)
        - [Translated Patterns](#translated-patterns)
- [Matcher](#matcher)
    - [Basepath](#basepath)
    - [Exceptions](#exceptions)
        - [Not Found](#not-found)
        - [Method not Allowed](#method-not-allowed)

## Introduction

Welcome to the fastest PHP router system docs! Before starting the usage is recommended that you have understanded the main goal and mission of all parts of this package.

### Performance

Codeburner project create packages with performance in focus, the Codeburner Router was compared with [Nikic's fast route](https://github.com/nikic/fastroute) a fast and base package for several route systems, including the [Laravel](http://laravel.com) and [SlimFramework](http://slimframework.com).

The Tests reveals that Codeburner Router can be in average **70% faster** while give a full abstraction level of handling routes. Here are some [blackfire](http://blackfire.io) comparison of two scripts that maps 100 routes with several arguments and execute them. More details about the benchmark including the comparisons and the script will be published asap.

### Connecting URLs to Code

The router recognizes URLs and dispatches them to a action. For example, when the application receive an incoming request for:

```php
"GET" "/article/17"
```

It asks the router to match it to a action, if the first matching route is:

```php
$collector->get("/article/{id}", "ArticleController@show");
```

The request is dispatched to the `ArticleController`'s `show` method with `17` as parameter.


## Usage Example

After a successful installation via composer, or a manual including of `Collector.php` and `Matcher.php` you can begin using the package.

```php
use Codeburner\Router\Collector;
use Codeburner\Router\Matcher;

$collector = new Collector();
$matcher   = new Matcher($collector);

$collector->get("/", function () {
    echo "Hello World!";
});

$route = $matcher->match("get", "/");
$route->call();
```

## Routes

After the release of v2.0.0 all routes are objects, and can be handled in groups. All route attributes can be modified at run time, and you can store a route created in the `Codeburner\Router\Collector` in a var. Every time you create a route by any `Codeburner\Router\Collector` method, a new `Route` object is created and by default a `Group` is returned containing these route.

### Patterns

Patterns follows the popular definitions created by [FastRoute](https://github.com/nikic/FastRoute), if you are familiar with [Laravel](laravel.com) or [Slim Framework](slimframework.com) you will not have problems here.

If you not, all you need to know for now, is that dynamic segments in patterns, or vars or even parameters if you prefer, are defined inside `{` and `}`. All parts of pattern inside this will be captured by the matcher and passed to the action.

#### Constraints

Routes can define dynamic patterns, that dynamic segment can have a constraint of match, in other words you could define that these segment must be an `int` or a `uid`. The constraint definition follows the pattern adopted by most of routers, for example, to enforce the format of slugs the constraint must be something that have letters, numbers and hyphens, not more.

```php
"/articles/{article:[a-z0-9-]+}"
```

> **NOTE:** As a constraint is essentially a portion of a bigger REGEX there is a restriction of use of capturing groups. For example `{lang:(en|de)}` is not a valid placeholder, because `()` is a capturing group. Instead you can use either `{lang:en|de}` or `{lang:(?:en|de)}`.

##### Wildcards

THe collector came with support to wildcards in place of regexes in constraints, you can define your own wildcards with the `setWildcard(string name, string regex)` collector's method. There are 8 wildcards with more 3 aliases in a total of 11 wildcards, that are listed bellow:

- uid: `uid-[a-zA-Z0-9]`
- slug: `[a-z0-9-]`
- string: `\w`
- int or integer: `\d`
- float or double: `[-+]?\d*?[.]?\d`
- hex: `0[xX][0-9a-fA-F]`
- octal: `0[1-7][0-7]`
- bool or boolean: `1|0|true|false|yes|no`

> **NOTE:** All the build-in **wildcards came with no quantifier**, but support [quantifiers](https://msdn.microsoft.com/en-us/library/3206d374.aspx) after they use, it's not a rule.

#### Optional Segments

You can define several patterns at once, with optional segments that can be nested. For optinal segments in your routes use the `[` and `]` statement to embrace the optional part. Optional segments must only be in the end of pattern and close all opened `[` with `]`. For example:

```php
"/user/photos[/{id:uid}]"
```

#### Unicode Characters

You can specify unicode character routes directly. For example:

```php
"/こんにちは"
```

### Actions

Routes define a pattern that indicates what request must execute what action. An action support all the definitions ways of a [callables](http://php.net/manual/language.types.callable.php) of PHP, plus a string containing a class name and one method of these class separated by a `@`.

All the parameters defined on the route pattern are accessible to be used on a dynamic action definition. All parameters will be snake-cased, and words separator are identified by a "-" character. In the example bellow if we request `/photos/get/30` the `PhotosController`'s `getLimited` method will be called with `30` as parameter.


```php
"/{resource:string+}/get/{count:int+}" "{resource}Controller@getLimited"
```

#### Strategies

A route must be able to execute the action. By default the action is executed by a simple `call_user_func_array` call, but you can define a more specific way to do that individually for each route or group with the `setStrategy` method, so each route can have different behaviors.

To define a new strategy simply create a class that implements the `Codeburner\Router\Strategies\StrategyInterface` interface and the `call` method that receives the `Codeburner\Router\Route` matched as unique parameter. A strategy can receive the `Codeburner\Router\Matcher` using the interface `Codeburner\Router\Strategy\MatcherAwareInterface`.

> **NOTE:** If it is necessary to use a container for creating the controller, please use the `setControllerCreatingFunction` method on the `Codeburner\Router\Route` object got in the `match` method of the `Codeburner\Router\Matcher`. It receives the same definition of action BUT requires return of the controller object.

##### Enhancers

The route enhancer strategy act like a bridge between one route and it dispatch strategy. In this "bridge" operations are made manipulating the route object to adapt it, it's common to use the [metadata informations](#defaults-and-metadata) in this place.

The real strategy is defined in the route metadata, using the key `strategy`, after the execution of the enhancement logic the real strategy will be called.

To create one enhancer you only need to extends the `Codeburner\Router\Strategies\EnhancerAbstractStrategy`.

### Defaults and Metadata

Sometimes you want to delegate more information to a route, for post match filters, strategies or even define default parameters that will be passed to the action even if they don't exist on route pattern for example.

For defining parameters that will be passed to actions use the `setDefault(string key, mixed value)` method, and for persist data that will not be passed to action but used in somewhere before the execution use the `setMetadata(string key, mixed value)` method.

For getting the metadata or the defined default parameters use the correspondent `Codeburner\Router\Route` getter methods, `getDefaults()` or `getMetadatas()` for getting all of each, and `getDefault(string key)` and `getMetadata(string key)` to get a specific default parameter as same as there is `hasDefault(string key)` and `hasMetadata(string key)`.

> **NOTE:** For maintain the methods name convention the `getMetadatas()` have to be named with a wrong spelling, look the S at the end. 

## Collector

The collector hold all routes and give to the matcher, and more important than that, implements all the abstraction layer of defining routes.

### Supported Methods

The collector has convenience methods for setting routes that will respond differently depending on the HTTP request method by default. Each of the above routes will respond to the same URI but will invoke a different action based on the HTTP request method.

```php
// Default route definition method.
$collector->set("get", "/", "controller@action");

// Supported HTTP methods wrappers.
$collector->get("/", "controller@action");
$collector->post("/", "controller@action");
$collector->put("/", "controller@action");
$collector->patch("/", "controller@action");
$collector->delete("/", "controller@action");

// Will register in any http method.
$collector->any("/", "controller@action");

// Will register in any given http method.
$collector->match(["get", "post"], "/", "controller@action");

// Will register in any request method but the given ones.
$collector->except(["put", "delete"], "/", "controller@action");
```

> **NOTE:** Routing both GET and POST requests to a single action has security implications. In general, you should avoid routing all verbs to an action unless you have a good reason to.

### Groups

All routes returned by the collector are `Codeburner\Router\Group` instances, even if it's a single route. With these groups you can use most of the `Codeburner\Router\Route` methods but applying the changes to all routes in the group. You can create groups with the `Codeburner\Router\Collector`'s `group` method that receive an array of routes.

#### Namespaces

For namespacing controller names, avoiding to rewrite the namespace everytime, you could use the default PHP syntax or use one of the two forms provided by the collector. The first one is using a instance of `Codeburner\Router\Group`.

```php
$collector->group([
    $collector->get("/", "controller@action"),
    // ...
])->setNamespace("foo\\");
```

> **NOTE:** The alias name must be writen with an D because namespace is a PHP reserved keyword.

#### Prefixes

As for namespaces there is two ways to define a prefix to a group of routes, first using an instance of `Codeburner\Router\Group`:

```php
$collector->group([
    $collector->get("/", "controller@action"),
    // ...
])->setPrefix("/foo");
```

#### Strategies

For example, applying a strategy to several routes at same time:

```php
$collector->group([
    $collector->get("/", "controller@action"),
    // ...
])->setStrategy("MyCustomStrategy");
```

#### Constraints

It's focused to `Codeburner\Router\Resources` but, you can define constraints to grouped routes too.

```php
$collector->group([
    $collector->get("/foo/{id}", "controller@foo"),
    $collector->get("/bar/{id}", "controller@bar")
])->setConstraint("id", "int+");
```

> **TIP:** Try using the `Codeburner\Router\Collector`'s `setWildcard` method for global constraints.

### Controllers

Controllers can be fully mapped by the `Codeburner\Router\Collector`, avoiding the manually description of routes to controller actions. To reach this abstraction some definitions must be respected:

- Methods that can be matched **must** begin with the corresponding HTTP method, like `get`, `post`, `put`, `patch` and `delete`.
- Camelcased method name will be converted to pattern, each word by default will receive `/` by prefix.

```php
class UserController
{
    public function getName()
    {
        // the same as $collector->get("/user/name", "UserController@getName")
    }
}
```

#### Changing Action Join

If you wanna change the default pattern joiner `/` by another join like `-`, you only need to define that before the call of `Codeburner\Router\Collector`'s `controller` method.

In the example bellow the pattern constructed by the `getName` method of `UserController` will be `/user-name` instead of `/user/name`.

```php
$collector->setControllerActionJoin("-");
$collector->controller("UserController");
```

#### Prefixing Controllers or Ignoring Auto-Prefix

By default all controller patterns receive the controller name as prefix, on previous example the `UserController` generate a `/user` prefix.

You can avoid this by using the `controllerWithoutPrefix(string controller)` instead of `controller(string controller, array option = null)` method, the same way for multiple matching methods `controllers(string[] controllers)` and `controllersWithoutPrefix(string[] controllers)`.

Or pass an array with the `as` option to the `controller(string controller, array option = null)`, these option will be used as prefix. eg.

```php
// now the pattern for getName method will be /account/name
$collector->controller("UserController", ["as" => "account"]);
```

#### Defining Multiple Controllers at Same Time

If you need to create routes for more than one controller, you can save a bit of typing by defining them all with a single call to controllers:

```php
$collector->controllers(["PhotosController", "BooksController", "VideosController"]);
```

This works exactly the same as:

```php
$collector->controller("PhotosController");
$collector->controller("BooksController");
$collector->controller("VideosController");
```


#### Annotated Information

All the [PHPDoc @param](http://www.phpdoc.org/docs/latest/references/phpdoc/tags/param.html) are parsed and the methods arguments receive a [constraint](#constraints). All the [wildcards](#wildcards) are allowed here, and you can set the type of argument as an [constraint](#constraints) too.

A new annotation is available for defining [strategies](#strategies) to specific methods. For this use the `@strategy` annotation.

```php
class BlogController
{
    /**
     * @param int $id
     * @annotation MyActionExcecutorStrategy
     */
    public function getPost($id)
    {
        // the same as $collector->get("/blog/post/{id:int}", "BlogController@getPost")
        //                       ->setStrategy("MyActionExecutorStrategy")
    }
}
```

### Resources

Resource routing allows you to quickly declare all of the common routes for a given resourceful controller. Instead of declaring separate routes for your index, show, make, edit, create, update and destroy actions, a resourceful route declares them in a single line of code.

```php
$collector->resource('PhotosController');
```

The collector will create seven new routes for `PhotosController`, as listed bellow:

Method   |Path               |Controller#Action         |Used For
---------|-------------------|--------------------------|---------------------------------------------
GET      | /photos           | PhotosController#index   | Display a list of all photos
GET      | /photos/make      | PhotosController#make    | Return an HTML form for creating a new photo
POST     | /photos           | PhotosController#create  | Create a new photo
GET      | /photos/{id}      | PhotosController#show    | Display a specific photo
GET      | /photos/{id}/edit | PhotosController#edit    | Return an HTML form for editing a photo
PUT      | /photos/{id}      | PhotosController#update  | Update a specific photo
DELETE   | /photos/{id}      | PhotosController#destroy | Delete a specific photo

> **NOTE:** Because the router uses the HTTP verb and URL to match inbound requests, four URLs map to seven different actions.

#### Prefixing Resources or Ignoring Auto-Prefix

Works exactly the same way as [controllers](#prefixing-controllers-or-ignoring-auto-prefix) but with `resource(string resource, array options = null)` method.

#### Defining Multiple Resources at Same Time

The same behavior of [defining multiple controllers at same time](#defining-multiple-controllers-at-same-time).
The multiple resource collector method is `resources(string[] resources)`.

#### Restricting Created Routes

There is two ways to define what of the seven resource routes should be created, with the `only` or `except` as option in `resource(string resource, array options = null)` method, 

```php
// create only the index and show routes.
$collector->resource("ArticleController", ["only" => ["index", "show"]]);

// create only the index and show routes too, because all the others should not be created.
$collector->resource("ArticleController", ["except" => ["make", "create", "destroy", "update", "edit"]]);
```

or with the `only` and `except` methods of `Codeburner\Router\Resource` object returned by the `resource(string resource, array options = null)` method.

```php
// create only the index and show routes.
$collector->resource("ArticleController")->only(["index", "show"]);

// create only the index and show routes too, because all the others should not be created.
$collector->resource("ArticleController")->except["make", "create", "destroy", "update", "edit"]);
```

#### Nested Resources

It's common to have resources that are logically children of other resources. For example one `article` always have one `category`. Nested routes allow you to capture this relationship in your routing. In this case, you could include this route declaration:

```php
$collector->resource("CategoryController")->nest(
    $collector->resource("ArticleController")
);
```

In addition to the routes for `CategoryController`, this declaration will also route to `ArticleController` with one category as parameter.

Method   |Path                                          |Controller#Action          |Used For
---------|----------------------------------------------|---------------------------|-----------------------------------------------
GET      | /category/{category_id}/article              | ArticleController#index   | Display a list of all Article
GET      | /category/{category_id}/article/make         | ArticleController#make    | Return an HTML form for creating a new article
POST     | /category/{category_id}/article              | ArticleController#create  | Create a new article
GET      | /category/{category_id}/article/{id}         | ArticleController#show    | Display a specific article
GET      | /category/{category_id}/article/{id}/edit    | ArticleController#edit    | Return an HTML form for editing a article
PUT      | /category/{category_id}/article/{id}         | ArticleController#update  | Update a specific article
DELETE   | /category/{category_id}/article/{id}         | ArticleController#destroy | Delete a specific article

##### Limits to Nesting

You can nest resources within other nested resources if you like. For example:

```php
$collector->resource("CategoryController")->nest(
    $collector->resource("ArticleController")->nest(
        $collector->resource("CommentController")
    )
);
```
Deeply-nested resources quickly become cumbersome. In this case, for example, the application would recognize paths such as:

```php
"/category/1/article/2/comment/3"
```

> **TIP:** Resources should never be nested more than 1 level deep.

##### Shallowed Resources

One way to avoid deep nesting (as recommended above) is to generate the collection actions scoped under the parent, so as to get a sense of the hierarchy, but to not nest the member actions. In other words, to only build routes with the minimal amount of information to uniquely identify the resource, like this:

```php
$collector->resource("ArticleController")->nest(
    $collector->resource("CommentController")->only(["index", "make", "create"]);
);

$collector->resource("CommentController")->except(["index", "make", "create"]);
```

This idea strikes a balance between descriptive routes and deep nesting. There exists shorthand syntax to achieve just that, via the `shallow` method in `Codeburner\Router\Resource`:

```php
$collector->resource("ArticleController")->shallow(
    $collector->resource("CommentController")
);
```

This will generate the exact same routes as the first example.

> **NOTE:** `shallow` method act the same way as `nest` method, so you can always nest these methods, and use one with each other. 

##### Adding More RESTFul Actions

You are not limited to the seven routes that RESTful routing creates by default. If you like, you may add additional routes that apply to the `Codeburner\Router\Resource`. The example above will create an additional route with `/photos/{id}/preview` pattern in `get` method.

```php
$collector->resource("PhotosController")->member(
    $collector->get("/preview", "PhotosController@preview")
);
```

##### Translated Patterns

If you prefer to translate the patterns generated by the resource, just define an `translate` option that receives an array with one or the two keys, `new` and `edit`.

```php
$collector->resource("ArticleController", ["as" => "kategorien", "translate" => ["new" => "neu", "edit": "bearbeiten"]);
```

Or using the `translate(array translations)` method of `Codeburner\Router\Resource`.

```php
$collector->resource("ArticleController", ["as" => "kategorien"])->translate(["new" => "neu", "edit": "bearbeiten"]);
``` 

The two examples above translate `ArticleController` routes to german, changing the prefix to `kategorien` and the `new` and `edit` keywords to `neu` and `bearbeiten` respectively.

## Matcher

The matcher is responsable for determining which route should be executed for a given request information.

### Basepath

An important point of matcher is that it can remove the basepath prefix from the routes patterns, for this the first parameter of the matcher constructor should be a string with the basepath.

So if you want to declare routes for a blog system living in `https://www.yourdomain.com/blog` create a new matcher that ignore the `/blog`, so all you declarations can skip this segment.

### Exceptions

#### Not Found
Route not found exception `Codeburner\Router\Exceptions\NotFoundException`

```php
try {
    // ...
    $route = $matcher->match("post", "foo");
    // ...
} catch (Codeburner\Router\Exceptions\NotFoundException $e) {
    // show some not found page.
    die("Request failed for method {$e->requested_method} and uri {$e->requested_uri}");
}
```

#### Method not Allowed
Route method is wrong `Codeburner\Router\Exceptions\MethodNotAllowedException`

```php
$collector->get("/foo", "controller@action");

try {
    $matcher->match("post", "/foo");
} catch (Codeburner\Router\Exceptions\MethodNotAllowedException $e) {
    // You can for example, redirect to the correct request.
    // this if verify if the requested route can serve get requests.
    if ($e->can("get")) {
        // if so, dispatch into get method.
        $matcher->match("get", $e->requested_uri);
    }
}
```

> **NOTE:** The HTTP specification requires that a `405 Method Not Allowed` response include the
`Allow:` header to detail available methods for the requested resource. For this you can get a
string with a processed allowed methods by using the `allowed` method of this exception.
