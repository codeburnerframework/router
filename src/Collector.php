<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2016 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router;

use Codeburner\Router\Exceptions\BadRouteException;
use LogicException, BadMethodCallException;

/**
 * Explicit Avoiding autoload for classes and traits
 * that are aways needed. Don't need an condition of class exists
 * because the routes will not be used until the collector is used.
 */

include_once __DIR__ . "/Route.php";
include_once __DIR__ . "/Parser.php";

/**
 * The Collector class hold, parse and build routes.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class Collector
{

    const HTTP_METHODS = ["get", "post", "put", "patch", "delete"];

    /**
     * A map of all resource routes.
     *
     * @var array
     */

    const RESOURCEFUL = [
        "index"  => ["get",    "/{name}"],
        "create" => ["post",   "/{name}"],
        "make"   => ["get",    "/{name}/make"],
        "show"   => ["get",    "/{name}/{id:int+}"],
        "edit"   => ["get",    "/{name}/{id:int+}/edit"],
        "update" => ["put",    "/{name}/{id:int+}"],
        "delete" => ["delete", "/{name}/{id:int+}"],
    ];

    /**
     * The static routes are simple stored in a multidimensional array, the first
     * dimension is indexed by an http method and hold an array indexed with the patterns
     * and holding the route. ex. [METHOD => [PATTERN => ROUTE]]
     *
     * @var Route[][]
     */

    protected $statics  = [];

    /**
     * The dynamic routes have parameters and are stored in a hashtable that every cell have
     * an array with route patterns as indexes and routes as values. ex. [INDEX => [PATTERN => ROUTE]]
     *
     * @var Route[][]
     */

    protected $dynamics = [];

    /**
     * @var Route[]
     */

    protected $named = [];

    /**
     * The pattern parser instance.
     *
     * @var Parser
     */

    protected $parser;

    /**
     * Collector constructor.
     *
     * @param Parser|null $parser
     */

    public function __construct(Parser $parser = null)
    {
        $this->parser = $parser ?: new Parser;
    }

    /**
     * @param string $method
     * @param string $pattern
     * @param callable $action
     *
     * @throws BadRouteException 
     * @throws MethodNotSupportedException
     *
     * @return Route|Group
     */

    public function set(string $method, string $pattern, $action)
    {
        $method   = strtolower($method);
        $patterns = $this->parser->parsePattern($pattern);

        if (count($patterns) === 1) {
            return $this->setRoute($method, $patterns[0], $action);
        }

        $group = new Group;

        foreach ($patterns as $pattern) {
            $group->setRoute($this->setRoute($method, $pattern, $action));
        }

        return $group;
    }

    /**
     * Define and register a new single Route onto the collector.
     *
     * @param string $method
     * @param string $pattern
     * @param callable $action
     *
     * @return Route
     */

    private function setRoute(string $method, string $pattern, $action) : Route
    {
        $route = new Route($this, $method, $pattern, $action);

        if (strpos($pattern, "{") !== false) {
               $index = $this->getDynamicIndex($method, $pattern);
               $this->dynamics[$index][$pattern] = $route;
        } else $this->statics[$method][$pattern] = $route;

        return $route;
    }

    public function get   (string $pattern, $action) { return $this->set("get"   , $pattern, $action); }
    public function post  (string $pattern, $action) { return $this->set("post"  , $pattern, $action); }
    public function put   (string $pattern, $action) { return $this->set("put"   , $pattern, $action); }
    public function patch (string $pattern, $action) { return $this->set("patch" , $pattern, $action); }
    public function delete(string $pattern, $action) { return $this->set("delete", $pattern, $action); }

    /**
     * Insert a route into several http methods.
     *
     * @param string[] $methods
     * @param string $pattern
     * @param callable $action
     *
     * @return Group
     */

    public function match(array $methods, string $pattern, $action) : Group
    {
        $group = new Group;

        foreach ($methods as $method) {
            $group->set($this->set($method, $pattern, $action));
        }

        return $group;
    }

    /**
     * Insert a route into every http method supported.
     *
     * @param string $pattern
     * @param callable $action
     *
     * @return Group
     */

    public function any(string $pattern, $action) : Group
    {
        return $this->match(static::HTTP_METHODS, $pattern, $action);
    }

    /**
     * Insert a route into every http method supported but the given ones.
     *
     * @param string|string[] $methods
     * @param string $pattern
     * @param callable $action
     *
     * @return Group
     */

    public function except($methods, string $pattern, $action) : Group
    {
        return $this->match(array_diff(static::HTTP_METHODS, (array) $methods), $pattern, $action);
    }

    /**
     * Resource routing allows you to quickly declare all of the common routes for a given resourceful controller. 
     * Instead of declaring separate routes for your index, show, new, edit, create, update and destroy actions, 
     * a resourceful route declares them in a single line of code.
     *
     * @param string $resource The resource class full name.
     * @param string $name The resource custom defined name.
     *
     * @return Resource
     */

    public function resource(string $resource, string $name = "") : Resource
    {
        if ($name === "") {
            $namespaces = explode("\\", $resource);
            $name       = str_replace(["controller", "resource"], "", strtolower(end($namespaces)));
        }

        $resource = new Resource;

        foreach (static::RESOURCEFUL as $action => $map) {
            $resource->set(
                $this->set($map[0], str_replace("{name}", $map[1], $name) , [$resource, $action])
                     ->setName("$name.$action")
            );
        }

        return $resource;
    }

    /**
     * Register several resources at same time.
     *
     * @param array $resources All resource full names.
     * @return Resource[]
     */

    public function resources(array $resources) : array
    {
        $returns = [];

        foreach ($resources as $resource) {
            $returns[] = $this->resource($resource);
        }

        return $returns;
    }

    /**
     * Group all given routes.
     *
     * @param array $routes
     * @return Group
     */

    public function group(array $routes) : Group
    {
        $group = new Group;

        foreach ($routes as $route) {
            $group->set($route);
        }

        return $group;
    }

    /**
     * Remove a route from collector.
     *
     * @param string $method
     * @param string $pattern
     */

    public function forget(string $method, string $pattern)
    {
        if (strpos($pattern, "{") === false) {
               unset($this->statics[$method][$pattern]);
        } else unset($this->dynamics[$this->getDynamicIndex($method, $pattern)][$pattern]);
    }

    /**
     * @param string $name
     * @return Route|null
     */

    public function findNamedRoute(string $name)
    {
        return $this->named[$name] ?? null;
    }

    /**
     * @param string $method
     * @param string $pattern
     *
     * @return Route|null
     */

    public function findStaticRoute(string $method, string $pattern)
    {
        $method = strtolower($method);

        if (isset($this->statics[$method]) && isset($this->statics[$method][$pattern])) {
               return $this->statics[$method][$pattern];
        } else return null;
    }

    /**
     * @param string $method
     * @param string $pattern
     *
     * @return array|null
     */

    public function findDynamicRoutes(string $method, string $pattern)
    {
        $index = $this->getDynamicIndex($method, $pattern);

        return $this->dynamics[$index] ?? null;
    }

    /**
     * @param string $method
     * @param string $pattern
     *
     * @return int
     */

    protected function getDynamicIndex(string $method, string $pattern) : int
    {
        return crc32(strtolower($method)) + substr_count($pattern, "/");
    }

    /**
     * @param string $name
     * @param Route $route
     *
     * @return self
     */

    public function setRouteName(string $name, Route $route) : self
    {
        $this->named[$name] = $route;

        return $this;
    }

    /**
     * Generate a path to a route named by $name.
     *
     * @param string $name
     * @param array $args
     *
     * @throws BadMethodCallException
     * @return string
     */

    public function getPath(string $name, array $args = []) : string
    {
        $parser  = $this->getParser();
        $route   = $this->findNamedRoute($name);
        $pattern = $route->getPattern();

        preg_match_all("~" . $parser::DYNAMIC_REGEX . "~x", $pattern, $matches, PREG_SET_ORDER);

        foreach ((array) $matches as $key => $match) {
            if (!isset($args[$match[1]])) {
                throw new BadMethodCallException("Missing argument '{$match[1]}' on creation of link for '{$name}' route.");
            }

            $pattern = str_replace($match[0], $args[$match[1]], $pattern);
        }

        return $pattern;
    }

    /**
     * @return Parser
     */

    public function getParser() : Parser
    {
        return $this->parser;
    }

    /**
     * @param Parser $parser
     *
     * @throws LogicException
     * @return self
     */

    public function setParser(Parser $parser) : static
    {
        if (!empty($this->statics) || !empty($this->dynamics)) {
            throw new LogicException("You can't define a route parser after registering a route.");
        }

        $this->parser = $parser;

        return $this;
    }
    
}
