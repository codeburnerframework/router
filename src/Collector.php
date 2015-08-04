<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router;

/**
 * Codeburner Router Component.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @see https://github.com/codeburnerframework/router
 */
class Collector
{

    /**
     * The route collection.
     *
     * @var \Codeburner\Router\Collection
     */
    protected $collection;

    /**
     * Supported HTTP methods.
     *
     * @var array
     */
    protected $methods = ['get', 'post', 'put', 'patch', 'delete'];

    /**
     * Construct the route dispatcher.
     *
     * @param \Codeburner\Router\Collection $collection The collection to save routes.
     */
    public function __construct(Collection $collection = null)
    {
        $this->collection = $collection ?: new Collection;
    }

    /**
     * Register a route into GET method.
     *
     * @param string         $pattern The URi pattern that should be matched.
     * @param string|closure $action  The action that must be executed in case of match.
     */
    public function get($pattern, $action)
    {
        $this->collection->set('get', $pattern, $action);
    }

    /**
     * Register a route into POST method.
     *
     * @param string         $pattern The URi pattern that should be matched.
     * @param string|closure $action  The action that must be executed in case of match.
     */
    public function post($pattern, $action)
    {
        $this->collection->set('post', $pattern, $action);
    }

    /**
     * Register a route into PUT method.
     *
     * @param string         $pattern The URi pattern that should be matched.
     * @param string|closure $action  The action that must be executed in case of match.
     */
    public function put($pattern, $action)
    {
        $this->collection->set('put', $pattern, $action);
    }

    /**
     * Register a route into PATCH method.
     *
     * @param string         $pattern The URi pattern that should be matched.
     * @param string|closure $action  The action that must be executed in case of match.
     */
    public function patch($pattern, $action)
    {
        $this->collection->set('patch', $pattern, $action);
    }

    /**
     * Register a route into DELETE method.
     *
     * @param string         $pattern The URi pattern that should be matched.
     * @param string|closure $action  The action that must be executed in case of match.
     */
    public function delete($pattern, $action)
    {
        $this->collection->set('delete', $pattern, $action);
    }

    /**
     * Register a route into all HTTP methods.
     *
     * @param string         $pattern The URi pattern that should be matched.
     * @param string|closure $action  The action that must be executed in case of match.
     */
    public function any($pattern, $action)
    {
        $this->match($this->methods, $pattern, $action);
    }

    /**
     * Register a route into all HTTP methods except by $method.
     *
     * @param string|array   $method  The method(s) that must be excluded.
     * @param string         $pattern The URi pattern that should be matched.
     * @param string|closure $action  The action that must be executed in case of match.
     */
    public function except($method, $pattern, $action)
    {
        $this->match(array_diff($this->methods, (array) $method), $pattern, $action);
    }

    /**
     * Register a route into given HTTP method(s).
     *
     * @param string|array   $methods The method(s) that must be excluded.
     * @param string         $pattern The URi pattern that should be matched.
     * @param string|closure $action  The action that must be executed in case of match.
     */
    public function match($methods, $pattern, $action)
    {
        foreach ((array) $methods as $method) {
            $this->collection->set($method, $pattern, $action);
        }
    }

    /**
     * Maps all the controller methods that begins with a HTTP method, and maps the rest of
     * name as a uri. The uri will be the method name with slashes before every camelcased 
     * word and without the HTTP method prefix. 
     * e.g. getSomePage will generate a route to: GET some/page
     *
     * @param string|object The controller name or representation.
     */
    public function controller($controller)
    {
        if (!$methods = get_class_methods($controller)) {
            throw new \Exception('The controller class coul\'d not be inspected.');
        }

        $methods = $this->getControllerMethods($methods);

        foreach ($methods as $httpmethod => $classmethods) {
            foreach ($classmethods as $classmethod) {
                $uri = preg_replace(
                    '/(^|[a-z])([A-Z])/e', 
                    'strtolower(strlen("\\1") ? "\\1/\\2" : "\\2")',
                    $classmethod 
                );

                $this->collection->set($httpmethod, "/$uri", "$controller#$httpmethod$classmethod");   
            }
        }
    }

    /**
     * Maps the controller methods to HTTP methods.
     *
     * @param array $methods All the controller public methods
     * @return array An array keyed by HTTP methods and their controller methods.
     */
    protected function getControllerMethods($methods)
    {
        $mapmethods = [];

        foreach ($methods as $classmethod) {
            foreach ($this->methods as $httpmethod) {
                if (($pos = strpos($classmethod, $httpmethod)) === 0) {
                    $mapmethods[$httpmethod][] = substr($classmethod, strlen($httpmethod));
                }
            }
        }

        return $mapmethods;
    }

    /**
     * Get the getCollection() of routes.
     *
     * @return \Codeburner\Router\Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

}
