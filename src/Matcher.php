<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2016 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router;

use Codeburner\Router\Exceptions\Http\MethodNotAllowedException;
use Codeburner\Router\Exceptions\Http\NotFoundException;
use Exception;

/**
 * The matcher class find the route for a given http method and path.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class Matcher
{

    /**
     * @var Collector
     */

    protected $collector;

    /**
     * Define a basepath to all routes.
     *
     * @var string
     */

    protected $basepath = "";

    /**
     * Construct the route dispatcher.
     *
     * @param Collector $collector
     * @param string $basepath Define a Path prefix that must be excluded on matches.
     */

    public function __construct(Collector $collector, $basepath = "")
    {
        $this->collector = $collector;
        $this->basepath  = $basepath;
    }

    /**
     * Find a route that matches the given arguments.
     * 
     * @param string $httpMethod
     * @param string $path
     *
     * @throws NotFoundException
     * @throws MethodNotAllowedException
     *
     * @return Route
     */

    public function match($httpMethod, $path)
    {
        $path = $this->parsePath($path);

        if ($route = $this->collector->findStaticRoute($httpMethod, $path)) {
            return $route;
        }

        if ($route = $this->matchDynamicRoute($httpMethod, $path)) {
            return $route;
        }

        $this->matchSimilarRoute($httpMethod, $path);
    }

    /**
     * Find and return the request dynamic route based on the compiled data and Path.
     *
     * @param string $httpMethod
     * @param string $path
     *
     * @return Route|false If the request match an array with the action and parameters will
     *                     be returned otherwise a false will.
     */

    protected function matchDynamicRoute($httpMethod, $path)
    {
        if ($routes = $this->collector->findDynamicRoutes($httpMethod, $path)) {
            // chunk routes for smaller regex groups using the Sturges' Formula
            foreach (array_chunk($routes, round(1 + 3.3 * log(count($routes))), true) as $chunk) {
                array_map([$this, "buildRoute"], $chunk);
                list($pattern, $map) = $this->buildGroup($chunk);

                if (!preg_match($pattern, $path, $matches)) {
                    continue;
                }

                /** @var Route $route */
                $route = $map[count($matches)];
                unset($matches[0]);

                $route->setParams(array_combine($route->getParams(), array_filter($matches)));
                $route->setMatcher($this);

                return $route;
            }
        }

        return false;
    }

    /**
     * Parse the dynamic segments of the pattern and replace then for
     * corresponding regex.
     *
     * @param Route $route
     * @return Route
     */

    protected function buildRoute(Route $route)
    {
        if ($route->getBlock()) {
            return $route;
        }

        list($pattern, $params) = $this->parsePlaceholders($route->getPattern());
        return $route->setPatternWithoutReset($pattern)->setParams($params)->setBlock(true);
    }

    /**
     * Group several dynamic routes patterns into one big regex and maps
     * the routes to the pattern positions in the big regex.
     *
     * @param Route[] $routes
     * @return array
     */

    protected function buildGroup(array $routes)
    {
        $groupCount = (int) $map = $regex = [];

        foreach ($routes as $route) {
            $params           = $route->getParams();
            $paramsCount      = count($params);
            $groupCount       = max($groupCount, $paramsCount) + 1;
            $regex[]          = $route->getPattern() . str_repeat("()", $groupCount - $paramsCount - 1);
            $map[$groupCount] = $route;
        }

        return ["~^(?|" . implode("|", $regex) . ")$~", $map];
    }

    /**
     * Parse an route pattern seeking for parameters and build the route regex.
     *
     * @param string $pattern
     * @return array 0 => new route regex, 1 => map of parameter names
     */

    protected function parsePlaceholders($pattern)
    {
        $params = [];
        preg_match_all("~" . Collector::DYNAMIC_REGEX . "~x", $pattern, $matches, PREG_SET_ORDER);

        foreach ((array) $matches as $key => $match) {
            $pattern = str_replace($match[0], isset($match[2]) ? "({$match[2]})" : "([^/]+)", $pattern);
            $params[$key] = $match[1];
        }

        return [$pattern, $params];
    }

    /**
     * Get only the path of a given url.
     *
     * @param string $path The given URL
     *
     * @throws Exception
     * @return string
     */

    protected function parsePath($path)
    {
        $path = parse_url(substr(strstr(";" . $path, ";" . $this->basepath), strlen(";" . $this->basepath)), PHP_URL_PATH);

        if ($path === false) {
            throw new Exception("Seriously malformed URL passed to route matcher.");
        }

        return $path;
    }

    /**
     * Generate an HTTP error request with method not allowed or not found.
     *
     * @param string $httpMethod
     * @param string $path
     *
     * @throws NotFoundException
     * @throws MethodNotAllowedException
     */

    protected function matchSimilarRoute($httpMethod, $path)
    {
        $dm = [];

        if (($sm = $this->checkStaticRouteInOtherMethods($httpMethod, $path))
                || ($dm = $this->checkDynamicRouteInOtherMethods($httpMethod, $path))) {
            throw new MethodNotAllowedException($httpMethod, $path, array_merge((array) $sm, (array) $dm));
        }

        throw new NotFoundException;
    }

    /**
     * Verify if a static route match in another method than the requested.
     *
     * @param string $targetHttpMethod The HTTP method that must not be checked
     * @param string $path              The Path that must be matched.
     *
     * @return array
     */

    protected function checkStaticRouteInOtherMethods($targetHttpMethod, $path)
    {
        return array_filter($this->getHttpMethodsBut($targetHttpMethod), function ($httpMethod) use ($path) {
            return (bool) $this->collector->findStaticRoute($httpMethod, $path);
        });
    }

    /**
     * Verify if a dynamic route match in another method than the requested.
     *
     * @param string $targetHttpMethod The HTTP method that must not be checked
     * @param string $path             The Path that must be matched.
     *
     * @return array
     */

    protected function checkDynamicRouteInOtherMethods($targetHttpMethod, $path)
    {
        return array_filter($this->getHttpMethodsBut($targetHttpMethod), function ($httpMethod) use ($path) {
            return (bool) $this->matchDynamicRoute($httpMethod, $path);
        });
    }

    /**
     * Strip the given http methods and return all the others.
     *
     * @param string|string[]
     * @return array
     */

    protected function getHttpMethodsBut($targetHttpMethod)
    {
        return array_diff(explode(" ", Collector::HTTP_METHODS), (array) $targetHttpMethod);
    }

    /**
     * @return Collector
     */

    public function getCollector()
    {
        return $this->collector;
    }

    /**
     * @return string
     */

    public function getBasePath()
    {
        return $this->basepath;
    }

    /**
     * Set a new basepath, this will be a prefix that must be excluded in
     * every requested Path.
     *
     * @param string $basepath The new basepath
     */
    
    public function setBasePath($basepath)
    {
        $this->basepath = $basepath;
    }

}
