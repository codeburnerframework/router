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
use Codeburner\Router\Exceptions\MethodNotSupportedException;

/**
 * Explicit Avoiding autoload for classes and traits
 * that are aways needed. Don't need an condition of class exists
 * because the routes will not be used until the collector is used.
 */

if (!class_exists(Parser::class, false)) {
    include __DIR__ . "/Parser.php";
}

include __DIR__ . "/Route.php";
include __DIR__ . "/Group.php";
include __DIR__ . "/Collectors/ControllerCollectorTrait.php";
include __DIR__ . "/Collectors/ResourceCollectorTrait.php";

/**
 * The Collector class hold, parse and build routes.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class Collector
{

    use Collectors\ControllerCollectorTrait;
    use Collectors\ResourceCollectorTrait;


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

    public function set($method, $pattern, $action)
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

    public function get   ($pattern, $action) { return $this->set("get"   , $pattern, $action); }
    public function post  ($pattern, $action) { return $this->set("post"  , $pattern, $action); }
    public function put   ($pattern, $action) { return $this->set("put"   , $pattern, $action); }
    public function patch ($pattern, $action) { return $this->set("patch" , $pattern, $action); }
    public function delete($pattern, $action) { return $this->set("delete", $pattern, $action); }

    /**
     * Insert a route into several http methods.
     *
     * @param string[] $methods
     * @param string $pattern
     * @param callable $action
     *
     * @return Group
     */

    public function match(array $methods, $pattern, $action)
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

    public function any($pattern, $action)
    {
        return $this->match(explode(" ", self::HTTP_METHODS), $pattern, $action);
    }

    /**
     * Insert a route into every http method supported but the given ones.
     *
     * @param string $methods
     * @param string $pattern
     * @param callable $action
     *
     * @return Group
     */

    public function except($methods, $pattern, $action)
    {
        return $this->match(array_diff(explode(" ", self::HTTP_METHODS), (array) $methods), $pattern, $action);
    }

    /**
     * Group all given routes.
     *
     * @param Route[] $routes
     * @return Group
     */

    public function group(array $routes)
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

    public function forget($method, $pattern)
    {
        if (strpos($pattern, "{") === false) {
               unset($this->statics[$method][$pattern]);
        } else unset($this->dynamics[$this->getDynamicIndex($method, $pattern)][$pattern]);
    }

    /**
     * @param string $name
     * @return Route|false
     */

    public function findNamedRoute($name)
    {
        if (!isset($this->named[$name])) {
               return false;
        } else return $this->named[$name];
    }

    /**
     * @param string $method
     * @param string $pattern
     *
     * @return Route|false
     */

    public function findStaticRoute($method, $pattern)
    {
        $method = strtolower($method);
        if (isset($this->statics[$method]) && isset($this->statics[$method][$pattern]))
            return $this->statics[$method][$pattern];
        return false;
    }

    /**
     * @param string $method
     * @param string $pattern
     *
     * @return array|false
     */

    public function findDynamicRoutes($method, $pattern)
    {
        $index = $this->getDynamicIndex($method, $pattern);
        return isset($this->dynamics[$index]) ? $this->dynamics[$index] : false;
    }

    /**
     * @param string $method
     * @param string $pattern
     *
     * @return int
     */

    protected function getDynamicIndex($method, $pattern)
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

    protected function getValidMethod($method)
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

    public function setRouteName($name, Route $route)
    {
        $this->named[$name] = $route;
        return $this;
    }

    /**
     * @return string[]
     */

    public function getWildcards()
    {
        return $this->parser->getWildcards();
    }

    /**
     * @return string[]
     */

    public function getWildcardTokens()
    {
        return $this->parser->getWildcardTokens();
    }

    /**
     * @param string $wildcard
     * @return string|null
     */

    public function getWildcard($wildcard)
    {
        return $this->parser->getWildcard($wildcard);
    }

    /**
     * @param string $wildcard
     * @param string $pattern
     *
     * @return self
     */

    public function setWildcard($wildcard, $pattern)
    {
        $this->parser->setWildcard($wildcard, $pattern);
        return $this;
    }

    /**
     * @return Parser
     */

    public function getParser()
    {
        return $this->parser;
    }

    /**
     * @param Parser $parser
     *
     * @throws \LogicException
     * @return self
     */

    public function setParser(Parser $parser)
    {
        if (!empty($this->statics) || !empty($this->dynamics)) {
            throw new \LogicException("You can't define a route parser after registering a route.");
        }

        $this->parser = $parser;
        return $this;
    }
    
}
