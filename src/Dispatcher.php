<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router;

use Codeburner\Router\Strategies\Dispatcher\StrategyInterface as DispatcherStrategyInterface;
use Codeburner\Router\Strategies\Dispatcher\ConcreteUriStrategy as DefaultStrategy;

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
     * @var \Codeburner\Router\Strategies\DispatcherStrategyInterface
     */
    protected $strategy;

    /**
     * The route collection.
     *
     * @var \Codeburner\Router\Collection
     */
    protected $collection;

    /**
     * Define a basepath to all routes.
     *
     * @var string
     */
    protected $basepath;

    /**
     * Construct the route dispatcher.
     *
     * @param string $basepath Define a URI prefix that must be excluded on matches.
     * @param \Codeburner\Router\Collection $collection The collection to save routes.
     * @param \Codeburner\Router\Strategies\DispatcherStrategyInterface $strategy The strategy to dispatch matched route action.
     */
    public function __construct($basepath = '', Collection $collection = null, DispatcherStrategyInterface $strategy = null)
    {
        $this->basepath   = (string) $basepath;
        $this->collection = $collection ?: new Collection;
        $this->strategy   = $strategy   ?: new DefaultStrategy;
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
        $uri    = parse_url(substr(strstr(";$uri", ";{$this->basepath}"), strlen(";{$this->basepath}")), PHP_URL_PATH);

        if ($route = $this->collection->getStaticRoute($method, $uri)) {
            return $this->strategy->dispatch(
                $route['action'], 
                []
            );
        }

        if ($route = $this->matchDinamicRoute($this->collection->getCompiledDinamicRoutes($method), $uri)) {
            return $this->strategy->dispatch(
                $this->resolveDinamicRouteAction($route['action'], $route['params']), 
                $route['params']
            );
        }

        if ($quiet === true) {
            return false;
        }

        $this->dispatchNotFoundRoute($method, $uri);
    }

    /**
     * Find and return the request dinamic route based on the compiled data and uri.
     *
     * @param array  $routes All the compiled data from dinamic routes.
     * @param string $uri    The URi of request.
     *
     * @return array|false If the request match an array with the action and parameters will be returned
     *                     otherwide a false will.
     */
    protected function matchDinamicRoute($routes, $uri)
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

        if ($sm = ($this->checkStaticRouteInOtherMethods($method, $uri)) 
                || $dm = ($this->checkDinamicRouteInOtherMethods($method, $uri))) {
            throw new Exceptions\MethodNotAllowedException($method, $uri, array_merge((array) $sm, (array) $dm));
        }

        throw new Exceptions\NotFoundException($method, $uri);
    }

    /**
     * Verify if a static route match in another method than the requested.
     *
     * @param string $method The HTTP method that must not be checked
     * @param string $uri    The URi that must be matched.
     *
     * @return array
     */
    protected function checkStaticRouteInOtherMethods($method, $uri)
    {
        $methods = [];
        $staticRoutesCollection = $this->collection->getStaticRoutes();

        unset($staticRoutesCollection[$method]);

        foreach ($staticRoutesCollection as $method => $routes) {
            if (!isset($methods[$method]) && isset($routes[$uri])) {
                $methods[$method] = $routes[$uri];
            }
        }

        return $methods;
    }

    /**
     * Verify if a dinamic route match in another method than the requested.
     *
     * @param string $method The HTTP method that must not be checked
     * @param string $uri    The URi that must be matched.
     *
     * @return array
     */
    protected function checkDinamicRouteInOtherMethods($method, $uri)
    {
        $methods = [];
        $dinamicRoutesCollection = $this->collection->getDinamicRoutes();

        unset($dinamicRoutesCollection[$method]);

        foreach ($dinamicRoutesCollection as $method => $routes) {
            if (!isset($methods[$method]) 
                    && $route = $this->matchDinamicRoute(
                            $this->collection->getCompiledDinamicRoutes($method), $uri)) {
                $methods[$method] = $route;
            }
        }

        return $methods;
    }

    /**
     * Resolve dinamic action, inserting route parameters at requested points.
     *
     * @param string|array|closure $action The route action.
     * @param array                $params The dinamic routes parameters.
     *
     * @return string
     */
    protected function resolveDinamicRouteAction($action, $params)
    {
        if (is_array($action)) {
            foreach ($action as $key => $value) {
                if (is_string($value)) {
                    $action[$key] = str_replace(['{', '}'], '', str_replace(array_keys($params), array_values($params), $value));
                }
            }
        }

        return $action;
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

    /**
     * Get the actual base path of this dispatch.
     *
     * @return string
     */
    public function getBasePath()
    {
        return $this->basepath;
    }

    /**
     * Set a new basepath, this will be a prefix that must be excluded in every
     * requested URi.
     *
     * @param string $basepath The new basepath
     */
    public function setBasePath($basepath)
    {
        $this->basepath = $basepath;
    }

}
