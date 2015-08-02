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
class Dispatcher
{

    /**
     * The action dispatch strategy object.
     *
     * @var \Codeburner\Router\Strategies\DispacherstrategyInterface
     */
    protected $strategy;

    /**
     * The route collection.
     *
     * @var \Codeburner\Router\RouteCollectionInterface
     */
    protected $collection;

    /**
     * Construct the route dispatcher.
     *
     * @param \Codeburner\Router\Strategies\DispacherstrategyInterface $strategy   The strategy to dispatch matched route action.
     * @param \Codeburner\Router\RouteCollectionInterface              $collection The collection to save routes.
     */
    public function __construct(
        Strategies\DispatcherStrategyInterface $strategy = null, 
        RouteCollectionInterface $collection = null
    ) {
        $this->strategy = $strategy ?: new Strategies\UriDispatcherStrategy;
        $this->collection = $collection ?: new RouteCollection;
    }

    /**
     * Find and dispatch a route based on the request http method and uri.
     *
     * @param string $method The HTTP method of the request, that should be GET, POST, PUT, PATCH or DELETE.
     * @param string $uri    The URi of request.
     * @param bool   $quiet  Should throw exception on match errors?
     *
     * @return mixed The request response
     */
    public function dispatch($method, $uri, $quiet = false)
    {
        $method = strtoupper($method);

        if ($route = $this->collection->getStaticRoute($method, $uri)) {
            return $this->strategy->dispatch($route['action'], $route['params']);
        }

        if ($route = $this->dispatchDinamicRoute($this->collection->getCompiledDinamicRoutes($method), $uri)) {
            return $this->strategy->dispatch($route['action'], $route['params']);
        }

        if ($quiet === true) {
            return false;
        }

        $this->dispatchNotFoundRoute($method, $uri);
    }

    /**
     * Find and dispatch dinamic routes based on the compiled data and uri.
     *
     * @param array  $routes All the compiled data from dinamic routes.
     * @param string $uri    The URi of request.
     * @return array|false If the request match an array with the action and parameters will be returned
     *                     otherwide a false will.
     */
    protected function dispatchDinamicRoute($routes, $uri)
    {
        foreach ($routes as $route) {
            if (!preg_match($route['regex'], $uri, $matches)) {
                continue;
            }

            list($action, $params) = $route['map'][count($matches)];

            $parameters = [];
            $i = 0;

            foreach ($params as $name) {
                $parameters[$name] = $matches[++$i];
            }

            return ['action' => $action, 'params' => $parameters];
        }

        return false;
    }

    /**
     * Generate an HTTP error request with method not allowed or not found.
     *
     * @param array  $routes All the compiled data from dinamic routes.
     * @param string $uri    The URi of request.
     *
     * @throws \Codeburner\Router\Exceptions\NotFoundException
     * @throws \Codeburner\Router\Exceptions\MethodNotAllowedException
     */
    protected function dispatchNotFoundRoute($method, $uri)
    {
        if ($this->checkStaticRouteInOtherMethods($method, $uri) 
                || $this->checkDinamicRouteInOtherMethods($method, $uri)) {
            throw new Exceptions\MethodNotAllowedException;
        }

        throw new Exceptions\NotFoundException;
    }

    /**
     * Verify if a static route match in another method than the requested.
     *
     * @param string $method The HTTP method that must not be checked
     * @param string $uri    The URi that must be matched.
     * @return bool
     */
    protected function checkStaticRouteInOtherMethods($method, $uri)
    {
        foreach ($this->collection->getStaticRoutes() as $other_method => $routes) {
            if ($other_method != $method && isset($routes[$uri])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify if a dinamic route match in another method than the requested.
     *
     * @param string $method The HTTP method that must not be checked
     * @param string $uri    The URi that must be matched.
     * @return bool
     */
    protected function checkDinamicRouteInOtherMethods($method, $uri)
    {
        foreach ($this->collection->getDinamicRoutes() as $other_method => $routes) {
            if ($other_method != $method
                    && $this->dispatchDinamicRoute($this->collection->getCompiledDinamicRoutes($other_method), $uri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the collection of routes.
     *
     * @return \Codeburner\Router\RouteCollection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Get the current dispatch strategy.
     *
     * @return \Codeburner\Router\Strategy\DispatcherStrategyInterface
     */
    public function getStrategy()
    {
        return $this->strategy;
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
        $this->match(['get', 'post', 'put', 'patch', 'delete'], $pattern, $action);
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
        $this->match(array_diff(['get', 'post', 'put', 'patch', 'delete'], (array) $method), $pattern, $action);
    }

    /**
     * Register a route into given HTTP method(s).
     *
     * @param string|array   $method  The method(s) that must be excluded.
     * @param string         $pattern The URi pattern that should be matched.
     * @param string|closure $action  The action that must be executed in case of match.
     */
    public function match($methods, $pattern, $action)
    {
        foreach ((array) $methods as $method) {
            $this->collection->set($method, $pattern, $action);
        }
    }

}
