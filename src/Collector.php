<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2016 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router;

use Codeburner\Router\Exceptions\{BadRouteException, MethodNotSupportedException};
use LogicException;

/**
 * Explicit Avoiding autoload for classes and traits
 * that are aways needed. Don't need an condition of class exists
 * because the routes will not be used until the collector is used.
 */

include_once "./Route.php";
include_once "./Parser.php";
include_once "./Group.php";

/**
 * The Collector class hold, parse and build routes.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class Collector
{

    /**
     * All the supported http methods separated by spaces.
     *
     * @var string
     */

    const HTTP_METHODS = "get post put patch delete";

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
     * A map of all resource routes.
     *
     * @var array
     */

    protected $resourceful = [
        "index"  => ["get",    "/{name}"],
        "make"   => ["get",    "/{name}/make"],
        "create" => ["post",   "/{name}"],
        "show"   => ["get",    "/{name}/{id:int+}"],
        "edit"   => ["get",    "/{name}/{id:int+}/edit"],
        "update" => ["put",    "/{name}/{id:int+}"],
        "delete" => ["delete", "/{name}/{id:int+}"],
    ];

    /**
     * Define how controller actions names will be joined to form the route pattern.
     *
     * @var string
     */

    protected $controllerActionJoin = "/";

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
     * @return Group
     */

    public function set(string $method, string $pattern, $action) : Group
    {
        $method   = $this->getValidMethod($method);
        $patterns = $this->parser->parsePattern($pattern);
        $group    = new Group;

        foreach ($patterns as $pattern)
        {
            $route = new Route($this, $method, $pattern, $action);
            $group->setRoute($route);

            if (strpos($pattern, "{") !== false) {
                   $index = $this->getDynamicIndex($method, $pattern);
                   $this->dynamics[$index][$pattern] = $route;
            } else $this->statics[$method][$pattern] = $route;
        }

        return $group;
    }

    public function get   (string $pattern, $action) : Group { return $this->set("get"   , $pattern, $action); }
    public function post  (string $pattern, $action) : Group { return $this->set("post"  , $pattern, $action); }
    public function put   (string $pattern, $action) : Group { return $this->set("put"   , $pattern, $action); }
    public function patch (string $pattern, $action) : Group { return $this->set("patch" , $pattern, $action); }
    public function delete(string $pattern, $action) : Group { return $this->set("delete", $pattern, $action); }

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
        foreach ($methods as $method)
            $group->set($this->set($method, $pattern, $action));
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
        return $this->match(explode(" ", self::HTTP_METHODS), $pattern, $action);
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
        return $this->match(array_diff(explode(" ", self::HTTP_METHODS), (array) $methods), $pattern, $action);
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

        foreach ($this->resourceful as $action => $map) {
            $resource->set(
                $this->set($map[0], str_replace("{name}", $map[1], $alias) , [$resource, $action])
                     ->setName("$alias.$action")
            );
        }

        return $resource;
    }

    /**
     * Register several resources at same time.
     *
     * @param string ...$resources All resource full names.
     * @return Resource[]
     */

    public function resources(string ...$resources) : array
    {
        $resources = [];

        foreach ($resources as $resource) {
            $resources[] = $this->resource($resource);
        }

        return $resources;
    }

    /**
     * Maps all the controller methods that begins with a HTTP method, and maps the rest of
     * name as a path. The path will be the method name with slashes before every camelcased 
     * word and without the HTTP method prefix, and the controller name will be used to prefix
     * the route pattern. e.g. ArticlesController::getCreate will generate a route to: GET articles/create
     *
     * @param string $controller The controller name
     * @param string $prefix
     *
     * @throws \ReflectionException
     * @return Group
     */

    public function controller(string $controller, $prefix = null) : Group
    {
        $controller = new ReflectionClass($controller);
        $prefix     = $prefix === null ? $this->getControllerPrefix($controller) : $prefix;
        $methods    = $controller->getMethods(ReflectionMethod::IS_PUBLIC);
        return $this->collectControllerRoutes($controller, $methods, "/$prefix/");
    }

    /**
     * Maps several controllers at same time.
     *
     * @param string ...$controllers Controllers name.
     * @throws \ReflectionException
     * @return Group
     */

    public function controllers(string ...$controllers) : Group
    {
        $group = new Group;
        foreach ($controllers as $controller)
            $group->set($this->controller($controller));
        return $group;
    }

    /**
     * @param ReflectionClass $controller
     * @param ReflectionMethod[] $methods
     * @param string $prefix
     *
     * @return Group
     */

    private function collectControllerRoutes(ReflectionClass $controller, array $methods, string $prefix) : Group
    {
        $group = new Group;
        $controllerDefaultStrategy = $this->getAnnotatedStrategy($controller);

        foreach ($methods as $method) {
            $name = preg_split("~(?=[A-Z])~", $method->name);
            $http = $name[0];
            unset($name[0]);
 
            if (strpos(Collector::HTTP_METHODS, $http) !== false) {
                $action   = $prefix . strtolower(implode($this->controllerActionJoin, $name));
                $dynamic  = $this->getMethodConstraints($method);
                $strategy = $this->getAnnotatedStrategy($method);

                $route = $this->set($http, "$action$dynamic", [$controller->name, $method->name]);

                if ($strategy !== null) {
                       $route->setStrategy($strategy);
                } else $route->setStrategy($controllerDefaultStrategy);

                $group->set($route);
            }
        }

        return $group;
    }

    /**
     * @param ReflectionClass $controller
     *
     * @return string
     */

    private function getControllerPrefix(ReflectionClass $controller) : string
    {
        preg_match("~\@prefix\s([a-zA-Z\\\_]+)~i", (string) $controller->getDocComment(), $prefix);
        return isset($prefix[1]) ? $prefix[1] : str_replace("controller", "", strtolower($controller->getShortName()));
    }

    /**
     * @param \ReflectionMethod
     * @return string
     */

    private function getMethodConstraints(ReflectionMethod $method) : string
    {
        $beginPath = "";
        $endPath = "";

        if ($parameters = $method->getParameters()) {
            $types = $this->getParamsConstraint($method);

            foreach ($parameters as $parameter) {
                if ($parameter->isOptional()) {
                    $beginPath .= "[";
                    $endPath .= "]";
                }

                $beginPath .= $this->getPathConstraint($parameter, $types);
            }
        }

        return $beginPath . $endPath;
    }

    /**
     * @param ReflectionParameter $parameter
     * @param string[] $types
     *
     * @return string
     */

    private function getPathConstraint(ReflectionParameter $parameter, array $types) : string
    {
        $name = $parameter->name;
        $path = "/{" . $name;
        return isset($types[$name]) ? "$path:{$types[$name]}}" : "$path}";
    }

    /**
     * @param ReflectionMethod $method
     * @return string[]
     */

    private function getParamsConstraint(ReflectionMethod $method) : array
    {
        $params = [];
        preg_match_all("~\@param\s(" . implode("|", array_keys($this->getParser()->getWildcards())) . "|\(.+\))\s\\$([a-zA-Z0-1_]+)~i",
            $method->getDocComment(), $types, PREG_SET_ORDER);

        foreach ((array) $types as $type) {
            // if a pattern is defined on Match take it otherwise take the param type by PHPDoc.
            $params[$type[2]] = isset($type[4]) ? $type[4] : $type[1];
        }

        return $params;
    }

    /**
     * @param ReflectionClass|ReflectionMethod $reflector
     * @return string|null
     */

    private function getAnnotatedStrategy($reflector)
    {
        preg_match("~\@strategy\s([a-zA-Z\\\_]+)~i", (string) $reflector->getDocComment(), $strategy);
        return isset($strategy[1]) ? $strategy[1] : null;
    }

    /**
     * Group all given routes.
     *
     * @param Route ...$routes
     * @return Group
     */

    public function group(Route ...$routes) : Group
    {
        $group = new Group;
        foreach ($routes as $route)
            $group->set($route);
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
        if (isset($this->named[$name])) {
           return $this->named[$name];
       }
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
        }
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
        return isset($this->dynamics[$index]) ? $this->dynamics[$index] : null;
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
     * Determine if the http method is valid.
     *
     * @param string $method
     *
     * @throws MethodNotSupportedException
     * @return string
     */

    protected function getValidMethod(string $method) : string
    {
        $method = strtolower($method);

        if (strpos(self::HTTP_METHODS, $method) === false) {
            throw new MethodNotSupportedException($method);
        }

        return $method;
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

    public function setParser(Parser $parser) : self
    {
        if (!empty($this->statics) || !empty($this->dynamics)) {
            throw new LogicException("You can't define a route parser after registering a route.");
        }

        $this->parser = $parser;
        return $this;
    }

    /**
     * Define how controller actions names will be joined to form the route pattern.
     * Defaults to "/" so actions like "getMyAction" will be "/my/action". If changed to
     * "-" the new pattern will be "/my-action".
     *
     * @param string $join
     * @return self
     */

    public function setControllerActionJoin(string $join) : self
    {
        $this->controllerActionJoin = $join;
        return $this;
    }

    /**
     * @return string
     */

    public function getControllerActionJoin() : string
    {
        return $this->controllerActionJoin;
    }
    
}
