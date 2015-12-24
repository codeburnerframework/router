<?php 

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router;

use Codeburner\Router\Exceptions\BadRouteException;
use Codeburner\Router\Strategies\DispatcherStrategyInterface as StrategyInterface;
use Codeburner\Router\Mapper as Collection;
use Codeburner\Router\Exceptions\MethodNotAllowedException;
use Codeburner\Router\Exceptions\NotFoundException;
use Exception;

/**
 * The dispatcher class find and execute the callback of the appropriated route for a given HTTP method and URI.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @since 1.0.0
 */

class Dispatcher
{

    /**
     * The action dispatch strategy object.
     *
     * @var string
     */

    protected $strategy;

    /**
     * The route collection.
     *
     * @var Collection
     */

    protected $collection;

    /**
     * Define a basepath to all routes.
     *
     * @var string
     */

    protected $basepath;

    /**
     * The delimiter in the controller/method action espefication.
     *
     * @var string
     */

    protected $actionDelimiter = '#';

    /**
     * Construct the route dispatcher.
     *
     * @param Collection $collection The collection to save routes.
     * @param string     $basepath   Define a URI prefix that must be excluded on matches.
     * @param string     $strategy   The strategy to dispatch matched route action.
     */

    public function __construct(Collection $collection, $basepath = '', $strategy = 'Codeburner\Router\Strategies\UriDispatcherStrategy')
    {
        $this->collection = $collection;
        $this->basepath   = (string) $basepath;
        $this->strategy   = $strategy;
    }

    /**
     * Find and dispatch a route based on the request http method and uri.
     *
     * @param string $method The HTTP method of the request, that should be GET, POST, PUT, PATCH or DELETE.
     * @param string $uri    The URi of request.
     *
     * @throws NotFoundException
     * @throws MethodNotAllowedException
     * @return mixed The request response
     */

    public function dispatch($method, $uri)
    {
        if ($route = $this->match($method, $uri)) {
            if (is_string($route['action']) && strpos($route['action'], $this->actionDelimiter)) {
                   $action = explode($this->actionDelimiter, $route['action']);
            } else $action = $route['action'];

            $strategy = $this->getRouteStrategy($route['strategy']);
            return $strategy->dispatch($action, $route['params']);
        }

        $this->dispatchNotFoundRoute($method, $uri);
    }

    /**
     * Find a route that matches the given arguments.
     * 
     * @param string $method The HTTP method of the request, that should be GET, POST, PUT, PATCH or DELETE.
     * @param string $uri    The URi of request.
     *
     * @return array|false
     */

    public function match($method, $uri)
    {
        $method = $this->getHttpMethod($method);
        $uri = $this->getUriPath($uri);

        if ($route = $this->collection->getStaticRoute($method, $uri)) {
            return $route;
        }

        return $this->matchDynamicRoute($this->collection->getDynamicRoutes($method, $uri), $uri);
    }

    /**
     * Verify if the given http method is valid.
     *
     * @param  int|string $method
     * @throws Exception
     * @return int
     */

    protected function getHttpMethod($method)
    {
        $methods = Mapper::getMethods();

        if (in_array($method, $methods)) {
            return $method;
        }

        if (array_key_exists($method = strtolower($method), array_map('strtolower', $methods))) {
            return $methods[$method];
        }

        throw new Exception('The HTTP method given to the route dispatcher is not supported or is incorrect.');
    }

    /**
     * Get only the path of a given url or uri.
     *
     * @param string $uri The given URL
     *
     * @throws Exception
     * @return string
     */

    protected function getUriPath($uri)
    {
        $path = parse_url(substr(strstr(';' . $uri, ';' . $this->basepath), strlen(';' . $this->basepath)), PHP_URL_PATH);

        if ($path === false) {
            throw new Exception('Seriously malformed URL passed to route dispatcher.');
        }

        return $path;
    }

    /**
     * Find and return the request dynamic route based on the compiled data and uri.
     *
     * @param array  $routes All the compiled data from dynamic routes.
     * @param string $uri    The URi of request.
     *
     * @return array|false If the request match an array with the action and parameters will be returned
     *                     otherwise a false will.
     */

    protected function matchDynamicRoute($routes, $uri)
    {
        foreach ($routes as $route) {
            if (!preg_match($route['pattern'], $uri, $matches)) {
                continue;
            }

            list($action, $params, $strategy) = $route['map'][count($matches)];
            // removing the uri from array.
            unset($matches[0]);
            // sometimes null values come with the matches so the array_filter must be called first.
            $params = array_combine($params, array_filter($matches));

            return [
                'action'   => $this->resolveDynamicRouteAction($action, $params),
                'params'   => $params,
                'strategy' => $strategy
            ];
        }

        return false;
    }

    /**
     * Generate an HTTP error request with method not allowed or not found.
     *
     * @param string $method The HTTP method that must not be checked.
     * @param string $uri    The URi of request.
     *
     * @throws NotFoundException
     * @throws MethodNotAllowedException
     */

    protected function dispatchNotFoundRoute($method, $uri)
    {
        $sm = $dm = [];

        if ($sm = ($this->checkStaticRouteInOtherMethods($method, $uri)) 
                || $dm = ($this->checkDynamicRouteInOtherMethods($method, $uri))) {
            throw new MethodNotAllowedException($method, $uri, array_merge((array) $sm, (array) $dm));
        }

        throw new NotFoundException($method, $uri);
    }

    /**
     * Verify if a static route match in another method than the requested.
     *
     * @param string $jump_method The HTTP method that must not be checked
     * @param string $uri         The URi that must be matched.
     *
     * @return array
     */

    protected function checkStaticRouteInOtherMethods($jump_method, $uri)
    {
        return array_filter(array_diff_key(Mapper::getMethods(), [$jump_method => true]), function ($method) use ($uri) {
            return (bool) $this->collection->getStaticRoute($method, $uri);
        });
    }

    /**
     * Verify if a dynamic route match in another method than the requested.
     *
     * @param string $jump_method The HTTP method that must not be checked
     * @param string $uri         The URi that must be matched.
     *
     * @return array
     */

    protected function checkDynamicRouteInOtherMethods($jump_method, $uri)
    {
        return array_filter(array_diff_key(Mapper::getMethods(), [$jump_method => true]), function ($method) use ($uri) {
            return (bool) $this->matchDynamicRoute($this->collection->getDynamicRoutes($method, $uri), $uri);
        });
    }

    /**
     * Resolve dynamic action, inserting route parameters at requested points.
     *
     * @param string|array|\closure $action The route action.
     * @param array                $params The dynamic routes parameters.
     *
     * @return string
     */

    protected function resolveDynamicRouteAction($action, $params)
    {
        if ($action instanceof \Closure) {
            return $action;
        }

        return str_replace(['{', '}'], '', str_replace(array_keys($params), $params, $action));
    }

    /**
     * @return Collection
     */

    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @return StrategyInterface
     */

    public function getStrategy()
    {
        return $this->strategy;
    }

    /**
     * @param StrategyInterface $strategy
     */

    public function setStrategy(StrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
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

    /**
     * @return string
     */

    public function getActionDelimiter()
    {
        return $this->actionDelimiter;
    }

    /**
     * @param string $delimiter
     */

    public function setActionDelimiter($delimiter)
    {
        $this->actionDelimiter = (string) $delimiter;
    }

    /**
     * @param string $strategy
     * @throws BadRouteException
     * @return \Codeburner\Router\Strategies\DispatcherStrategyInterface
     */

    private function getRouteStrategy($strategy)
    {
        if ($strategy === null) {
            return is_string($this->strategy) ? $this->strategy = new $this->strategy : $this->strategy;
        }

        if (class_exists($strategy)) {
            return new $strategy;
        }

        throw new BadRouteException(BadRouteException::BAD_DISPATCH_STRATEGY);
    }

}
