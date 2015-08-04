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
class Collection
{

    const DINAMIC_REGEX = '\{\s*([a-zA-Z][a-zA-Z0-9_]*)\s*(?::\s*([^{}]*(?:\{(?-1)\}[^{}]*)*))?\}';
    const DEFAULT_PLACEHOLD_REGEX = '([^/]+)';

    /**
     * Routes without parameters.
     *
     * @var array
     */
    protected $statics = [];

    /**
     * Routes with parameters to compute.
     *
     * @var array
     */
    protected $dinamics = [];

    /**
     * Insert a route into the collection.
     *
     * @param string               $method  The HTTP method of route. {GET, POST, PUT, PATCH, DELETE}
     * @param string               $pattern The URi that route should match.
     * @param string|array|closure $action  The callback for when route is matched.
     */
    public function set($method, $pattern, $action)
    {
        $method = strtoupper($method);
        $patterns = $this->parsePatternOptionals($pattern);

        foreach ($patterns as $pattern){
            if (strpos($pattern, '{') !== false) {

                list($pattern, $params) = $this->parsePatternPlaceholders($pattern);
                $this->dinamics[$method][$pattern] = ['action' => $action, 'params' => $params];

            } else {

                $this->statics[$method][$pattern]  = ['action' => $action, 'params' => []];

            }
        }
    }

    /**
     * Separate routes pattern with optional parts into n new patterns.
     *
     * @param string $pattern The route pattern to parse.
     * @return array
     */
    protected function parsePatternOptionals($pattern)
    {
        $patternWithoutClosingOptionals = rtrim($pattern, ']');
        $numOptionals = strlen($pattern) - strlen($patternWithoutClosingOptionals);

        $segments = preg_split('~' . self::DINAMIC_REGEX . '(*SKIP)(*F) | \[~x', $patternWithoutClosingOptionals);

        if ($numOptionals !== count($segments) - 1) {
            if (preg_match('~' . self::DINAMIC_REGEX . '(*SKIP)(*F) | \]~x', $patternWithoutClosingOptionals)) {
                   throw new \Exception('Optional segments can only occur at the end of a route.');
            } else throw new \Exception('Number of opening \'[\' and closing \']\' does not match.');
        }

        $current  = '';
        $patterns = [];

        foreach ($segments as $n => $segment) {
            if ($segment === '' && $n !== 0) {
                throw new \Exception('Empty optional part.');
            }

            $current .= $segment;
            $patterns[] = $current;
        }

        return $patterns;
    }

    /**
     * Parse an route pattern seeking for parameters and making the route regex.
     *
     * @param string $pattern The route pattern to be parsed.
     * @return array 0 => new route regex, 1 => map of parameters names.
     */
    protected function parsePatternPlaceholders($pattern)
    {
        preg_match_all('~' . self::DINAMIC_REGEX . '~x', $pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        $params = [];

        foreach ((array) $matches as $match) {
            $pattern = str_replace($match[0][0], isset($match[2]) ? '(' . trim($match[2][0]) . ')' : self::DEFAULT_PLACEHOLD_REGEX, $pattern);
            $params[$match[1][0]] = $match[1][0];
        }

        return [$pattern, $params];
    }

    /**
     * Find and return a static route.
     *
     * @param string $method The HTTP method to search for.
     * @param string $uri    The static request URi to search.
     * @return array|null
     */
    public function getStaticRoute($method, $uri)
    {
        return isset($this->statics[$method]) && isset($this->statics[$method][$uri]) ? $this->statics[$method][$uri] : null;
    }

    /**
     * Get all static routes from a method or return then all.
     *
     * @param string $method The HTTP method to search for.
     * @return array
     */
    public function getStaticRoutes($method = null)
    {
        return !empty($method) && isset($this->statics[$method]) ? $this->statics[$method] : $this->statics;
    }

    /**
     * Get all dinamic routes from a method or return then all.
     *
     * @param string $method The HTTP method to search for.
     * @return array
     */
    public function getDinamicRoutes($method = null)
    {
        return !empty($method) && isset($this->dinamics[$method]) ? $this->dinamics[$method] : $this->dinamics;
    }

    /**
     * Concat all dinamic routes regex for a given method, this speeds up the match.
     *
     * @param string $method The http method to search in.
     * @return array [['regex', 'map' => [0 => action, 1 => params]]]
     */
    public function getCompiledDinamicRoutes($method)
    {
        if (!isset($this->dinamics[$method])) {
            return [];
        }

        $routes = $this->dinamics[$method];
        $count = count($routes);
        $chunksize = ceil($count / max(1, round($count / 10)));
        $chunks = array_chunk($routes, $chunksize, true);
        
        return array_map(function ($routes) {
            $map = [];
            $regexes = [];
            $groupcount = 0;

            foreach ($routes as $regex => $route) {
                $paramscount      = count($route['params']);
                $groupcount       = max($groupcount, $paramscount) + 1;
                $regexes[]        = $regex . str_repeat('()', $groupcount - $paramscount - 1);
                $map[$groupcount] = [$route['action'], $route['params']];
            }

            return [
                'regex' => '~^(?|' . implode('|', $regexes) . ')$~', 
                'map'   => $map
            ];
        }, $chunks);
    }

}
