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
use Codeburner\Router\Collectors\{ControllerCollectorTrait, ResourceCollectorTrait};
use LogicException;

/**
 * Explicit Avoiding autoload for classes and traits
 * that are aways needed. Don't need an condition of class exists
 * because the routes will not be used until the collector is used.
 */

include_once __DIR__ . "/Route.php";
include_once __DIR__ . "/Parser.php";
include_once __DIR__ . "/Group.php";
include_once __DIR__ . "/Collectors/ControllerCollectorTrait.php";
include_once __DIR__ . "/Collectors/ResourceCollectorTrait.php";

/**
 * The Collector class hold, parse and build routes.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class Collector
{

    use ControllerCollectorTrait;
    use ResourceCollectorTrait;

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
     * Group all given routes.
     *
     * @param Route[] $routes
     * @return Group
     */

    public function group(array $routes) : Group
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
    
}
