# Codeburner Router

[![Latest Stable Version](https://poser.pugx.org/codeburner/router/v/stable)](https://packagist.org/packages/codeburner/router)
[![Build Status](https://travis-ci.org/codeburnerframework/router.svg?branch=master)](https://travis-ci.org/codeburnerframework/router)
[![Code Coverage](https://scrutinizer-ci.com/g/codeburnerframework/routing/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/codeburnerframework/routing/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/codeburnerframework/routing/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/codeburnerframework/routing/?branch=master)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/d96c4a67-982b-4e16-a24d-7b490bf11bc7/big.png)](https://insight.sensiolabs.com/projects/d96c4a67-982b-4e16-a24d-7b490bf11bc7)

An blazing fast PHP router system with amazing features and abstraction.

Thanks to [Nikita Popov's](https://github.com/nikic/) for motivate me with [this post](https://nikic.github.io/2014/02/18/Fast-request-Router-using-regular-expressions.html).

## Installation

Add `codeburner/router` to your `composer.json` file, and update or install composer dependencies.

```json
{
    "require": {
        "codeburner/router": "^2.0"
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
    - [Concepts](#concepts)
    - [Usage](#usage)
- [Routes](#routes)
    - [Patterns](#patterns)
        - [Constraints](#constraints)
            - [Wildcards](#wildcards)
        - [Optional Segments](#optional-segments)
    - [Actions](#actions)
        - [Strategies](#strategies)
            - [Enhancers](#enhancers)
            - [PSR7](#psr7)
        - [Default Arguments](#default-arguments)
        - [Container Integration](#container-integration)
    - [Names](#names)
    - [Metadata](#metadata)
- [Collector](#collector)
    - [Groups](#groups)
    - [Resources](#resources)
        - [Restricting Actions](#restricting-actions)
        - [Prefixing Resources](#prefixing-resources)
            - [Ignoring Resource Name](#ignoring-resource-name)
        - [Nested Resources](#nested-resources)
            - [Nesting Limit](#nesting-limit)
                - [Shallow Resources](#shallow-resources)
            - [Adding More Actions](#adding-more-actions)
        - [Resources Route Names](#resources-route-names)
        - [Translated Patterns](#translated-patterns)
    - [Controllers](#controllers)
        - [Annotated Definition](#annotated-definition)
        - [Prefixing Controllers](#prefixing-controllers)
            - [Ignoring Controller Name](#ignoring-controller-name)
        - [Changing Pattern Join](#changing-pattern-join)
- [Matcher](#matcher)
    - [Exceptions](#exceptions)
        - [Method not Allowed](#method-not-allowed)
        - [List of Exceptions](#list-of-exceptions)
    - [Basepath](#basepath)

## Introduction

Welcome to the fastest PHP router system docs! Before starting the usage is recommended understand the main goal and mission of all parts of this package.

### Performance

Codeburner project create packages with performance in focus, the Codeburner Router was compared with [Nikic's fast route](https://github.com/nikic/fastroute) a fast and base package for several route systems, including the [Laravel](http://laravel.com) and [SlimFramework](http://slimframework.com).

The Tests reveals that Codeburner Router can be in average **70% faster** while give a full abstraction level in handling routes. More details about the benchmark including the comparisons using [blackfire](http://blackfire.io) of [scripts that maps 100 routes with several arguments and execute them](https://gist.github.com/alexrohleder96/bc302708653b68d1b053), can be found [here](https://blackfire.io/profiles/compare/b861281b-b6d4-4015-b25b-fd9399e789ba...914e81ac-0898-4633-81b1-6c9f1bbfab69/graph?settings%5Bdimension%5D=wt&settings%5Bdisplay%5D=landscape&settings%5BtabPane%5D=nodes&selected=&callname=main()).


### Concepts

The router recognize requests and maps to a logic, an action. For example, when the application receive an incoming request for:

```php
"GET" "/article/17"
```

It asks the router to match it to a action, if the first matching route is:

```php
$collector->get("/article/{id}", "ArticleResource::show");
```

The request is dispatched to the `ArticleResource`'s `show` method with `17` as parameter.


## Usage

After a successful installation via composer, or a manual including of `Collector.php` and `Matcher.php` you can begin using the package.

```php
include "vendor/autoload.php";

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

More examples can be found [here](https://www.github.com/codeburnerframework/router/tree/examples).


## Routes

After the release of v2.0.0 all routes are objects, and can be handled in groups. All route attributes can be modified at run time, and you can store a route created in the `Codeburner\Router\Collector` in a var. Every time you create a route by any `Codeburner\Router\Collector` method, a new `Route` object is created and by default a `Group` is returned containing these route.


### Patterns

Patterns are representation of request paths, it follows the popular definitions created by [FastRoute](https://github.com/nikic/FastRoute), if you are familiar with [Laravel](laravel.com) or [Slim Framework](slimframework.com) you will not have problems here.

If you not, all you need to know for now, is that dynamic segments in patterns, or vars or even parameters if you prefer, are defined inside `{` and `}`. All parts of pattern inside this will be captured by the matcher and passed to the action.


#### Constraints

Routes can define dynamic pattern segments, and that can have a constraint of match, in other words you could define that these segment must be an `int` or a `uid`. The constraint definition follows the pattern adopted by most of routers, for example, to enforce the format of slugs the constraint must be something that have letters, numbers and hyphens, not more.

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


### Actions

Routes define what request must execute what action. An action support all the definitions ways of a [callables](http://php.net/manual/language.types.callable.php) of PHP.

All the parameters defined on the route pattern are accessible to be used on a dynamic action definition. All parameters will be snake-cased, and words separator are identified by a "-" character. In the example bellow if we request `/photos/get/30` the `PhotosResource`'s `getLimited` method will be called with `30` as parameter.

```php
"/{resource:string+}/get/{count:int+}" "{resource}Controller::getLimited"
```


#### Strategies

A route must be able to execute the action. By default the action is executed by a simple `call_user_func_array` call, but you can define a more specific way to do that individually for each route or group with the `setStrategy` method, so each route can have different behaviors.

To define a new strategy simply create a class that implements the `Codeburner\Router\Strategies\StrategyInterface` interface and the `call` method that receives the `Codeburner\Router\Route` matched as unique parameter. A strategy can receive the `Codeburner\Router\Matcher` using the interface `Codeburner\Router\Strategy\MatcherAwareInterface`.


##### Enhancers

The route enhancer strategy act like a bridge between one route and it dispatch strategy. In this "bridge" operations are made manipulating the route object to adapt it, it's common to use the [metadata information](#metadata) in this place.

The real strategy is defined in the route metadata, using the key `strategy`, after the execution of enhancement logic the real strategy will be called.

To create one enhancer you only need to extends the `Codeburner\Router\Strategies\EnhancerAbstractStrategy`.


##### PSR7

Codeburner have support to [psr7 objects](http://www.php-fig.org/psr/psr-7/), there are at this version two strategies that give your actions a `request` and `response` objects and handle generated `response` objects. Both `Codeburner\Router\Strategies\RequestResponseStrategy` and `Codeburner\Router\Strategies\RequestJsonStrategy` receive one `Psr\Http\Message\RequestInterface` and one `Psr\Http\Message\ResponseInterface`, and make the `matcher`'s `call` method return a `Psr\Http\Message\ResponseInterface`.

```php
use Psr\Http\Message\{RequestInterface as Request, ResponseInterface as Response};
use Codeburner\Router\Strategies\{RequestResponseStrategy, RequestJsonStrategy};

$r1 = $collector->get('/article/{id}', function (Request $request, Response $response, array $args) {
    return getArticleById($args['id']);
});

$r2 = $collector->get("/article/{id}", function (Request $request, array $args) {
    return (array) getArticleById($args['id']);
});

$r1->setStrategy(new RequestResponseStrategy($request, $response));
$r2->setStrategy(new RequestJsonStrategy($request, $response));
```

> **TIP:** Instead of creating strategy objects by yourself, you could use a [container wrapper](#container-integration) on `call` method.


#### Default Arguments

If is necessary to define arguments that are not found on the pattern, use the `setDefault(string key, mixed value)` method. These arguments will be merged with the given from the pattern.

```php
$collector->get("/", function ($arg) {
    echo $arg;
})->setDefault("arg", "hello world");
```


#### Container Integration

If is necessary to inject dependencies on controllers, resources, or even strategies, you can tell the `call` method on `Route` objects to use a closure that receives the class name and return one instance of these class.

```php
$route->call(function ($class) use ($container) {
    return $container->get($class);
});
```


### Names

All the routes allow you to apply names to then, this names can be used to find a route in the `Collector` or to generate links with `Path`'s method `to(string name, array args = [])`. E.g.

```php
// ...
// The Path class will create several links for us, just give they new object a instance of the collector.
$path = new Codeburner\Router\Path($collector);
// ...
// Setting the name of route to blog.article
$collector->get("/blog/{article:slug+}", "blog::show")->setName("blog.article");
// ...
// this will print an anchor tag with "/blog/my-first-article" in href.
echo "<a href='", $path->to("blog.article", ["article" => "my-first-article"]), "'>My First Article</a>";
```

> **NOTE:** For best practice use the dot for delimiting namespaces in your route names, so you can group and find they names easily. The [resource](#resources-route-names) collector adopt this concept.


### Metadata

Sometimes you want to delegate more information to a route, for post match filters or action execution strategies. For persist data that will not be passed to action but used in somewhere before the execution use the `setMetadata(string key, mixed value)` method.

For getting the metadata use the `Codeburner\Router\Route`'s `getMetadata(string key = "")` method without the key parameter for getting all of each, or passing the key parameter to get a specific metadata, you can check if a metadata exists with `hasMetadata(string key)` method.


## Collector

The collector hold all routes and give to the matcher, and more important than that, implements all the abstraction layer of defining routes.


### Groups

All routes returned by the collector are `Codeburner\Router\Group` instances, even if it's a single route. With these groups you can use most of the `Codeburner\Router\Route` methods but applying the changes to all routes in the group. You can create groups with the `Codeburner\Router\Collector`'s `group` method that receive an array of routes or instantiating a new instance of `Codeburner\Router\Group` and add all routes with `set` method.


### Resources

Resource routing allows you to quickly declare all of the common routes for a given resourceful controller. Instead of declaring separate routes for your index, show, make, edit, create, update and destroy actions, a resourceful route declares them in a single line of code.

```php
$collector->resource(PhotosResource::class);
```

The collector will create seven new routes for `PhotosResource`, as listed bellow:

Method   |Path               |Controller::Action         |Used For
---------|-------------------|---------------------------|---------------------------------------------
GET      | /photos           | PhotosResource::index     | Display a list of all photos
GET      | /photos/make      | PhotosResource::make      | Return an HTML form for creating a new photo
POST     | /photos           | PhotosResource::create    | Create a new photo
GET      | /photos/{id}      | PhotosResource::show      | Display a specific photo
GET      | /photos/{id}/edit | PhotosResource::edit      | Return an HTML form for editing a photo
PUT      | /photos/{id}      | PhotosResource::update    | Update a specific photo
DELETE   | /photos/{id}      | PhotosResource::destroy   | Delete a specific photo

> **NOTE:** Because the router uses the HTTP verb and URL to match inbound requests, four URLs map to seven different actions.


#### Restricting Actions

There is two ways to define what of the seven resource routes should be created, with the `only` and `except` methods of `Codeburner\Router\Resource` object returned by the `resource(string ...resource)` method.

```php
$collector->resource(ArticleResource::class)->only("index", "show");
```

```php
$collector->resource(ArticleResource::class)->except("make", "create", "destroy", "update", "edit");
```

> **NOTE:** You can use the [variadic functions](http://php.net/manual/pt_BR/functions.arguments.php#functions.variable-arg-list) to pass an array to `only` and `except` methods.

#### Prefixing Resources

By default all resource patterns receive the resource name as prefix, on previous example the `UserResource` generate a `/user` prefix. To alter this use the `as` method, these option will be used as prefix. eg.

```php
// now the pattern for make action will be /account/make
$collector->resource(UserResource::class)->as("account");
```


##### Ignoring Resource Name

You can avoid this by using the `resourceWithoutPrefix(string ...resource)` instead of `resource(string ...resource)` method.


#### Nested Resources

It's common to have resources that are logically children of other resources. For example one `article` always have one `category`. Nested routes allow you to capture this relationship in your routing. In this case, you could include this route declaration:

```php
$collector->resource(CategoryResource::class)->nest(
    $collector->resource(ArticleResource::class)
);
```

In addition to the routes for `CategoryResource`, this declaration will also route to `ArticleResource` with one category as parameter.

Method   |Path                                          |Controller::Action          |Used For
---------|----------------------------------------------|----------------------------|-----------------------------------------------
GET      | /category/{category_id}/article              | ArticleResource::index     | Display a list of all Article
GET      | /category/{category_id}/article/make         | ArticleResource::make      | Return an HTML form for creating a new article
POST     | /category/{category_id}/article              | ArticleResource::create    | Create a new article
GET      | /category/{category_id}/article/{id}         | ArticleResource::show      | Display a specific article
GET      | /category/{category_id}/article/{id}/edit    | ArticleResource::edit      | Return an HTML form for editing a article
PUT      | /category/{category_id}/article/{id}         | ArticleResource::update    | Update a specific article
DELETE   | /category/{category_id}/article/{id}         | ArticleResource::destroy   | Delete a specific article

##### Nesting Limit

You can nest resources within other nested resources if you like. For example:

```php
$collector->resource("CategoryResource")->nest(
    $collector->resource("ArticleResource")->nest(
        $collector->resource("CommentResource")
    )
);
```
Deeply-nested resources quickly become cumbersome. In this case, for example, the application would recognize paths such as:

```php
"/category/1/article/2/comment/3"
```

> **TIP:** Resources should never be nested more than 1 level deep.

###### Shallow Resources

One way to avoid deep nesting (as recommended above) is to generate the collection actions scoped under the parent, so as to get a sense of the hierarchy, but to not nest the member actions. In other words, to only build routes with the minimal amount of information to uniquely identify the resource, like this:

```php
$collector->resource("ArticleResource")->nest(
    $collector->resource("CommentResource")->only(["index", "make", "create"]);
);

$collector->resource("CommentResource")->except(["index", "make", "create"]);
```

This idea strikes a balance between descriptive routes and deep nesting. There exists shorthand syntax to achieve just that, via the `shallow` method in `Codeburner\Router\Resource`:

```php
$collector->resource("ArticleResource")->shallow(
    $collector->resource("CommentResource")
);
```

This will generate the exact same routes as the first example.

> **NOTE:** `shallow` method act the same way as `nest` method, so you can always nest these methods, and use one with each other.

##### Adding More Actions

You are not limited to the seven routes that RESTFul routing creates by default. If you like, you may add additional routes that apply to the `Codeburner\Router\Resource`. The example above will create an additional route with `/photos/{id}/preview` pattern in `get` method.

```php
$collector->resource("PhotosResource")->member(
    $collector->get("/preview", "PhotosResource::preview")
);
```


#### Resources Route Names

All the routes in resource receive a [name](#names) that will be composed by the resource name or prefix, a dot and the action name. e.g.

```php
class PhotosResource {
    public function index() {

    }
}

$collector->resource("PhotosResource")->only("index");
$collector->resource("PhotosResource", ["as" => "picture"])->only("index");

echo $path->to("photos.index"), "<br>", $path->to("picture.index");
```


#### Translated Patterns

If you prefer to translate the patterns generated by the resource, just define an `translate` option that receives an array with one or the two keys, `new` and `edit`.

```php
$collector->resource("ArticleResource", ["as" => "kategorien", "translate" => ["new" => "neu", "edit": "bearbeiten"]);
```

Or using the `translate(array translations)` method of `Codeburner\Router\Resource`.

```php
$collector->resource("ArticleResource", ["as" => "kategorien"])->translate(["new" => "neu", "edit": "bearbeiten"]);
```

The two examples above translate `ArticleResource` routes to german, changing the prefix to `kategorien` and the `new` and `edit` keywords to `neu` and `bearbeiten` respectively.


### Controllers

Controllers can be fully mapped by the `Codeburner\Router\Collector`, avoiding the manually description of routes to controller actions. To reach this abstraction some definitions must be respected:

- Methods that can be matched **must** begin with the corresponding HTTP method, like `get`, `post`, `put`, `patch` and `delete`.
- Camelcased method name will be converted to pattern, each word by default will receive `/` by prefix.

```php
class UserController
{
    public function getName()
    {
        // the same as $collector->get("/user/name", "UserController::getName")
    }
}
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
        // the same as $collector->get("/blog/post/{id:int+}", "BlogController::getPost")
        //                       ->setStrategy("MyActionExecutorStrategy")
    }
}
```


#### Prefixing Controllers

Act the same way of [prefixing resources](#prefixing-resources), passing the option `as` to `controller(string controller, array options = null)` method.


##### Ignoring Controller Name

Same way of [ignoring resource name](#ignoring-resource-name), use the `controllerWithoutPrefix(string controller)` method, or the `controllersWithoutPrefix(string[] controllers)` method.


#### Changing Pattern Join

If you wanna change the default pattern joiner `/` by another join like `-`, you only need to define that before the call of `Codeburner\Router\Collector`'s `controller` method.

In the example bellow the pattern constructed by the `getName` method of `UserController` will be `/user-name` instead of `/user/name`.

```php
$collector->setControllerActionJoin("-");
$collector->controller("UserController");
```


## Matcher

The matcher is responsible for determining which route should be executed for a given request information.


### Basepath

An important point of matcher is that it can remove the basepath prefix from the routes patterns, for this the first parameter of the matcher constructor should be a string with the basepath.

So if you want to declare routes for a blog system living in `https://www.yourdomain.com/blog` create a new matcher that ignore the `/blog`, so all you declarations can skip this segment.


### Exceptions

There are several exceptions for HTTP errors provided by the codeburner router system, but there are special exceptions, that have methods for determining the logic of failure.


#### Method not Allowed
Route method is wrong `Codeburner\Router\Exceptions\Http\MethodNotAllowedException`

```php
$collector->get("/foo", "controller::action");

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

#### List of Exceptions

- Codeburner\Router\Exceptions\BadRouteException
    - Codeburner\Router\Exceptions\MethodNotSupportedException
- Codeburner\Router\Exceptions\Http\HttpExceptionAbstract
    - Codeburner\Router\Exceptions\Http\BadRequestException
    - Codeburner\Router\Exceptions\Http\ConflictException
    - Codeburner\Router\Exceptions\Http\ForbiddenException
    - Codeburner\Router\Exceptions\Http\GoneException
    - Codeburner\Router\Exceptions\Http\LengthRequiredException
    - Codeburner\Router\Exceptions\Http\MethodNotAllowedException
    - Codeburner\Router\Exceptions\Http\NotAcceptableException
    - Codeburner\Router\Exceptions\Http\NotFoundException
    - Codeburner\Router\Exceptions\Http\PaymentRequiredException
    - Codeburner\Router\Exceptions\Http\PreconditionFailedException
    - Codeburner\Router\Exceptions\Http\RequestTimeOutException
    - Codeburner\Router\Exceptions\Http\ServiceUnavailableException
    - Codeburner\Router\Exceptions\Http\UnauthorizedException
    - Codeburner\Router\Exceptions\Http\UnsupportedMediaTypeException

> **NOTE:** The HTTP specification requires that a `405 Method Not Allowed` response include the
`Allow:` header to detail available methods for the requested resource. For this you can get a
string with a processed allowed methods by using the `allowed` method of this exception.
