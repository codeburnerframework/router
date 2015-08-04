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
     * @var \Codeburner\Router\Strategies\StrategyAbstract 
     */
    protected $strategy;

    /**
     * The route collection.
     *
     * @var \Codeburner\Router\Collection
     */
    protected $collection;

    /**
     * Construct the route dispatcher.
     *
     * @param \Codeburner\Router\Collection                  $collector The collection to save routes.
     * @param \Codeburner\Router\Strategies\StrategyAbstract $strategy  The strategy to dispatch matched route action.
     */
    public function __construct(Collection $collection = null, Strategies\StrategyAbstract $strategy = null)
    {
        $this->collection = $collection ?: new Collection;
        $this->strategy   = $strategy   ?: new Strategies\UriStrategy;
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
     *
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
     * @param string $method The HTTP method that must not be checked.
     * @param string $uri    The URi of request.
     *
     * @throws \Codeburner\Router\Exceptions\NotFoundException
     * @throws \Codeburner\Router\Exceptions\MethodNotAllowedException
     */
    protected function dispatchNotFoundRoute($method, $uri)
    {
        $dm = $dm = [];

        if ($sm = $this->checkStaticRouteInOtherMethods($method, $uri) 
                || $dm = $this->checkDinamicRouteInOtherMethods($method, $uri)) {
            throw new Exceptions\MethodNotAllowedException($method, $uri, array_merge((array) $sm, (array) $dm));
        }

        throw new Exceptions\NotFoundException($method, $uri);
    }

    /**
     * Verify if a static route match in another method than the requested.
     *
     * @param string $method The HTTP method that must not be checked
     * @param string $uri    The URi that must be matched.
     * @return array
     */
    protected function checkStaticRouteInOtherMethods($method, $uri)
    {
        $methods = [];
        $staticRoutesCollection = $this->collection->getStaticRoutes();

        foreach ($staticRoutesCollection as $other_method => $routes) {
            if (!isset($methods[$other_method]) && $other_method != $method && isset($routes[$uri])) {
                $methods[$other_method] = $routes[$uri];
            }
        }

        return $methods;
    }

    /**
     * Verify if a dinamic route match in another method than the requested.
     *
     * @param string $method The HTTP method that must not be checked
     * @param string $uri    The URi that must be matched.
     * @return array
     */
    protected function checkDinamicRouteInOtherMethods($method, $uri)
    {
        $methods = [];
        $dinamicRoutesCollection = $this->collection->getDinamicRoutes();

        foreach ($dinamicRoutesCollection as $other_method => $routes) {
            if (!isset($methods[$other_method]) 
                    && $other_method != $method
                        && $route = $this->dispatchDinamicRoute(
                                $this->collection->getCompiledDinamicRoutes($other_method), $uri)) {
                $methods[$other_method] = $route;
            }
        }

        return $methods;
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

    /**
     * Get the current dispatch strategy.
     *
     * @return \Codeburner\Router\Strategy\AbstractStrategy
     */
    public function getStrategy()
    {
        return $this->strategy;
    }

}
