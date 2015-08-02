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
interface RouteCollectionInterface
{

    /**
     * Insert a route into the collection.
     *
     * @param string               $method  The HTTP method of route. {GET, POST, PUT, PATCH, DELETE}
     * @param string               $pattern The URi that route should match.
     * @param string|array|closure $action  The callback for when route is matched.
     */
    public function set($method, $pattern, $action);

    /**
     * Find and return a static route.
     *
     * @param string $method The HTTP method to search for.
     * @param string $uri    The static request URi to search.
     * @return array|null
     */
    public function getStaticRoute($method, $uri);

    /**
     * Get all static routes from a method or return then all.
     *
     * @param string $method The HTTP method to search for.
     * @return array
     */
    public function getStaticRoutes($method = null);

    /**
     * Get all dinamic routes from a method or return then all.
     *
     * @param string $method The HTTP method to search for.
     * @return array
     */
    public function getDinamicRoutes($method = null);

    /**
     * Concat all dinamic routes regex for a given method, this speeds up the match.
     *
     * @param string $method The http method to search in.
     * @return array [['regex', 'map' => [0 => action, 1 => params]]]
     */
    public function getCompiledDinamicRoutes($method);
    
}