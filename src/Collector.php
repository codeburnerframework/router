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
     * These regex define the structure of a dynamic segment in a pattern.
     *
     * @var string
     */

    const DYNAMIC_REGEX = "{\s*(\w*)\s*(?::\s*([^{}]*(?:{(?-1)}*)*))?\s*}";


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
     * @var array
     */

    protected $statics  = [];

    /**
     * The dynamic routes have parameters and are stored in a hashtable that every cell have
     * an array with route patterns as indexes and routes as values. ex. [INDEX => [PATTERN => ROUTE]]
     *
     * @var array
     */

    protected $dynamics = [];

    /**
     * Some regex wildcards for easily definition of dynamic routes. ps. all keys and values must start with :
     *
     * @var array
     */

    protected $wildcards = [
        ":uid"     => ":uid-[a-zA-Z0-9]",
        ":slug"    => ":[a-z0-9-]",
        ":string"  => ":\w",
        ":int"     => ":\d",
        ":integer" => ":\d",
        ":float"   => ":[-+]?\d*?[.]?\d",
        ":double"  => ":[-+]?\d*?[.]?\d",
        ":hex"     => ":0[xX][0-9a-fA-F]",
        ":octal"   => ":0[1-7][0-7]",
        ":bool"    => ":1|0|true|false|yes|no",
        ":boolean" => ":1|0|true|false|yes|no",
    ];

    /**
     * @param string $method
     * @param string $pattern
     * @param string|array|\Closure $action
     *
     * @throws BadRouteException 
     * @throws MethodNotSupportedException
     *
     * @return Group
     */

    public function set($method, $pattern, $action)
    {
        $method   = $this->parseMethod($method);
        $patterns = $this->parsePattern($pattern);
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
     * @param string|array|\Closure $action
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
     * @param string|array|\Closure $action
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
     * @param string|array|\Closure $action
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
     * Determine if the http method is valid.
     *
     * @param string $method
     * @throws MethodNotSupportedException
     * @return string
     */

    protected function parseMethod($method)
    {
        $method = strtolower($method);

        if (strpos(self::HTTP_METHODS, $method) === false) {
            throw new MethodNotSupportedException($method);
        }

        return $method;
    }

    /**
     * Separate routes pattern with optional parts into n new patterns.
     *
     * @param  string $pattern
     * @return array
     */

    protected function parsePattern($pattern)
    {
        $withoutClosing = rtrim($pattern, "]");
        $closingNumber  = strlen($pattern) - strlen($withoutClosing);

        $segments = preg_split("~" . self::DYNAMIC_REGEX . "(*SKIP)(*F)|\[~x", $withoutClosing);
        $this->parseSegments($segments, $closingNumber, $withoutClosing);

        return $this->buildSegments($segments);
    }

    /**
     * Parse all the possible patterns seeking for an incorrect or incompatible pattern.
     *
     * @param string[] $segments       Segments are all the possible patterns made on top of a pattern with optional segments.
     * @param int      $closingNumber  The count of optional segments.
     * @param string   $withoutClosing The pattern without the closing token of an optional segment. aka: ]
     *
     * @throws BadRouteException
     */

    protected function parseSegments(array $segments, $closingNumber, $withoutClosing)
    {
        if ($closingNumber !== count($segments) - 1) {
            if (preg_match("~" . self::DYNAMIC_REGEX . "(*SKIP)(*F)|\]~x", $withoutClosing)) {
                   throw new BadRouteException(BadRouteException::OPTIONAL_SEGMENTS_ON_MIDDLE);
            } else throw new BadRouteException(BadRouteException::UNCLOSED_OPTIONAL_SEGMENTS);
        }
    }

    /**
     * @param string[] $segments
     *
     * @throws BadRouteException
     * @return array
     */

    protected function buildSegments(array $segments)
    {
        $pattern  = "";
        $patterns = [];
        $wildcardTokens = array_keys($this->wildcards);
        $wildcardRegex  = $this->wildcards;

        foreach ($segments as $n => $segment) {
            if ($segment === "" && $n !== 0) {
                throw new BadRouteException(BadRouteException::EMPTY_OPTIONAL_PARTS);
            }

            $patterns[] = $pattern .= str_replace($wildcardTokens, $wildcardRegex, $segment);
        }

        return $patterns;
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
     * @return string[]
     */

    public function getWildcards()
    {
        $wildcards = [];
        foreach ($this->wildcards as $token => $regex)
            $wildcards[substr($token, 1)] = substr($regex, 1);
        return $wildcards;
    }

    /**
     * @return string[]
     */

    public function getWildcardTokens()
    {
        return $this->wildcards;
    }

    /**
     * @param string $wildcard
     * @return string|null
     */

    public function getWildcard($wildcard)
    {
        return isset($this->wildcards[":$wildcard"]) ? substr($this->wildcards[":$wildcard"], 1) : null;
    }

    /**
     * @param string $wildcard
     * @param string $pattern
     *
     * @return self
     */

    public function setWildcard($wildcard, $pattern)
    {
        $this->wildcards[":$wildcard"] = ":$pattern";
        return $this;
    }
    
}
