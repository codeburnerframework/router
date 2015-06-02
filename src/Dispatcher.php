<?php

namespace Codeburner\Routing;

use Codeburner\Routing\RouteFilterInterface;
use Codeburner\Routing\Exceptions\NotFoundException;
use Codeburner\Routing\Exceptions\MethodNotAllowedException;
use Codeburner\Routing\Exceptions\UnauthorizedException;

class Dispatcher
{

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
	 * Global conditions to a route group.
	 *
	 * @var array
	 */
	protected $conditions = [];

	/**
	 * All the registered filters to be used in some routes.
	 *
	 * @var array
	 */
	protected $filters = [];

	/**
	 * Registed route names
	 *
	 * @var array
	 */
	protected $aliases = [];

	/**
	 * Try to find a route that matches the parameters and execute their callback.
	 *
	 * @return null|false if quiet is enabled the dispatch fail will return false.
	 * @throws MethodNotAllowedException|NotFoundException
	 */
	public function dispatch($method, $uri, $quiet = false)
	{
		if (isset($this->statics[$method][$uri])) {
			return $this->call($this->statics[$method][$uri]);
		}

		$dinamics = $this->generateDinamicRouteData();

		if (isset($dinamics[$method])) {
			$result = $this->dispatchDinamicRoute($dinamics[$method], $uri);

			if ($result['found'] == true) {
				return $this->call($result);
			}
		}

		if ($quiet == true) {
			return false;
		}

		$inOtherMethods = [];

		foreach ($this->statics as $other_method => $map) {
			if ($other_method != $method and isset($map[$uri])) {
				$inOtherMethods[] = $other_method;
			}
		}

		foreach ($dinamics as $other_method => $data) {
			if ($other_method == $method) continue;
			
			$result = $this->dispatchDinamicRoute($data, $uri);

			if ($result[0] == true) $inOtherMethods[] = $other_method;
		}

		if ($inOtherMethods) {
			throw new MethodNotAllowedException;
		}

		throw new NotFoundException;
	}

	/**
	 * Register a new route into the dispatch stack.
	 *
	 * @param string $method
	 * @param string $pattern
	 * @param string|array $action
	 * @param string|array $filter (optional)
	 * @param string $name (optional)
	 */
	public function register($method, $pattern, $action, $filter = [], $name = '')
	{
		$data = $this->parse($pattern);
		$method = strtoupper($method);

		if (isset($this->conditions['prefix'])) {
			$data[0] = '/' . trim($this->conditions['prefix'], '/') . $data[0];
		}

		if (isset($this->conditions['namespace'])) {
			if (is_string($action) and strpos($action, '@')) {
				$action = rtrim($this->conditions['namespace'], '\\') . '\\' . $action;
			}
		}

		if ($this->isStaticRoute($data))
		{
			$this->registerRouteAlias($name, $pattern);
			
			$this->statics[$method][$data[0]] = ['action' => $action, 'filters' => (array) $filter];
		} 
		else 
		{
			list($pattern, $parameters) = $this->generateRouteRegex($data);

			$this->registerRouteAlias($name, $pattern);

			$this->dinamics[$method][$pattern] = ['action' => $action, 'parameters' => $parameters, 'filters' => (array) $filter];
		}
	}

	/**
	 * Register a new GET route into the dispatch stack.
	 *
	 * @param string $method
	 * @param string $pattern
	 * @param string|array $action
	 * @param string|array $filter (optional)
	 * @param string $name (optional)
	 */
	public function get($pattern, $action, $filter = [], $name = '')
	{
		$this->register('GET', $pattern, $action, $filter, $name);
	}

	/**
	 * Register a new POST route into the dispatch stack.
	 *
	 * @param string $method
	 * @param string $pattern
	 * @param string|array $action
	 * @param string|array $filter (optional)
	 * @param string $name (optional)
	 */
	public function post($pattern, $action, $filter = [], $name = '')
	{
		$this->register('POST', $pattern, $action, $filter, $name);
	}

	/**
	 * Register a new PUT route into the dispatch stack.
	 *
	 * @param string $method
	 * @param string $pattern
	 * @param string|array $action
	 * @param string|array $filter (optional)
	 * @param string $name (optional)
	 */
	public function put($pattern, $action, $filter = [], $name = '')
	{
		$this->register('PUT', $pattern, $action, $filter, $name);
	}

	/**
	 * Register a new PATCH route into the dispatch stack.
	 *
	 * @param string $method
	 * @param string $pattern
	 * @param string|array $action
	 * @param string|array $filter (optional)
	 * @param string $name (optional)
	 */
	public function patch($pattern, $action, $filter = [], $name = '')
	{
		$this->register('PATCH', $pattern, $action, $filter, $name);
	}

	/**
	 * Register a new DELETE route into the dispatch stack.
	 *
	 * @param string $method
	 * @param string $pattern
	 * @param string|array $action
	 * @param string|array $filter (optional)
	 * @param string $name (optional)
	 */
	public function delete($pattern, $action, $filter = [], $name = '')
	{
		$this->register('DELETE', $pattern, $action, $filter, $name);
	}

	/**
	 * Register a new HEAD route into the dispatch stack.
	 *
	 * @param string $method
	 * @param string $pattern
	 * @param string|array $action
	 * @param string|array $filter (optional)
	 * @param string $name (optional)
	 */
	public function head($pattern, $action, $filter = [], $name = '')
	{
		$this->register('HEAD', $pattern, $action, $filter, $name);
	}

	/**
	 * Register a new OPTIONS route into the dispatch stack.
	 *
	 * @param string $method
	 * @param string $pattern
	 * @param string|array $action
	 * @param string|array $filter (optional)
	 * @param string $name (optional)
	 */
	public function options($pattern, $action, $filter = [], $name = '')
	{
		$this->register('OPTIONS', $pattern, $action, $filter, $name);
	}

	/**
	 * Register a new route into the dispatch stack for all given methods.
	 *
	 * @param string $method
	 * @param string $pattern
	 * @param string|array $action
	 * @param string|array $filter (optional)
	 */
	public function map($methods, $action, $filter = [])
	{
		foreach ((array)$methods as $method) {
			$this->register($method, $pattern, $action, $filter, $name);
		}
	}

	/**
	 * Register a new route into the dispatch stack for all methods.
	 *
	 * @param string $method
	 * @param string $pattern
	 * @param string|array $action
	 * @param string|array $filter (optional)
	 */
	public function any($pattern, $action, $filter = [])
	{
		$this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'], $pattern, $action, $filter);
	}

	/**
	 * Register a group of routes that shares some conditions.
	 *
	 * @param string|array $conditions
	 * @param callback $register
	 */
	public function group($conditions, $register)
	{
		$old_conditions = $this->conditions;

		if (is_string($conditions)) {
			$conditions = ['prefix' => $conditions];
		}

		$this->conditions = array_merge($old_conditions, $conditions);

		$register($this);

		$this->conditions = $old_conditions;
	}

	/**
	 * Execute the given route action if no filter block the execution.
	 *
	 * @return Response|string
	 */
	public function call($route)
	{
		if (!empty($route['filters'])) {
			$this->callRouteFilters($route['filters']);
		}

		if (is_string($route['action'])) {
			if (strpos($route['action'], '{') >= 0) {
				$route['action'] = $this->generateDinamicRouteAction($route);
			}

			$route['action'] = explode('@', $route['action']);
		}

		call_user_func_array($route['action'], isset($route['parameters']) ? $route['parameters'] : []);
	}

	/**
	 * Register a new filter into the dispatcher.
	 *
	 * @param string $name
	 * @param Callable|RouteFilterInterface $filter
	 */
	public function filter($name, $filter)
	{
		$this->filters[$name] = $filter;
	}

	/**
	 * Make an URi for the given route name.
	 *
	 * @return string
	 */
	public function uri($name, $parameters = [])
	{
		if (isset($this->aliases[$name])) {
			if (empty($parameters)) {
				return $this->aliases[$name];
			}
		}

		return preg_replace_callback('/\((.+?)\)/', function ($match) use($parameters) {
			static $i = -1;
			return $parameters[++$i];
		}, $this->aliases[$name]);
	}

	/**
	 * Execute the given route filters.
	 *
	 * @param array $filters
	 */
	protected function callRouteFilters($filters)
	{
		if (is_string($filters)) {
			$filters = explode('|', $filters);
		}

		foreach ($filters as $filter)
		{
			$pass = false;

			if (isset($this->filters[$filter])) {
				if ($this->filters[$filters] instanceof RouteFilterInterface) {
					$pass = $this->filters[$filters]->call($route);
				} else {
					$pass = call_user_func($this->filters[$filters], $route);
				}
			}

			if ($pass == false) {
				throw new UnauthorizedException;
			}
		}
	}

	/**
	 * Replace the action name variables with the route parameters.
	 *
	 * @param array $route
	 * @return array
	 */
	protected function generateDinamicRouteAction($route)
	{
		return preg_replace_callback('/{(.+?)}/', function ($match) use ($route)
		{
			if (array_key_exists($match[1], $route['parameters']))
			{
				$return = $route['parameters'][$match[1]];

				unset($route['parameters'][$match[1]]);

				return $return;
			}

			return $match[0];
		}, $route['action']);
	}

	/**
	 * Register a new name for a route.
	 *
	 * @param string $alias
	 * @param string $pattern
	 */
	protected function registerRouteAlias($alias, $pattern)
	{
		if (!empty($alias)) {
			$this->aliases[$alias] = $pattern;
		}
	}

	/**
	 * Verify if the given route data is of an static route.
	 *
	 * @return boolean
	 */
	protected function isStaticRoute($data)
	{
		if (count($data) == 1 and is_string($data[0])) {
			return true;
		}

		return false;
	}

	/**
	 * Parse the route pattern seeking for some parameter.
	 *
	 * @return array
	 */
	protected function parse($pattern)
	{
		if (!preg_match_all('~\{\s* ([a-zA-Z][a-zA-Z0-9_]*) \s* (?: : \s* ([^{}]*(?:\{(?-1)\}[^{}]*)*))?\}~x', $pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
			return [$pattern];
		}

		$offset = 0;
        $data = [];

        foreach ($matches as $set) {
            if ($set[0][1] > $offset) {
                $data[] = substr($pattern, $offset, $set[0][1] - $offset);
            }

            $data[] = [$set[1][0], isset($set[2]) ? trim($set[2][0]) : '[^/]+'];
            $offset = $set[0][1] + strlen($set[0][0]);
        }

        if ($offset != strlen($pattern)) {
            $data[] = substr($pattern, $offset);
        }

        return $data;
	}

	/**
	 * Create a new regex with a concatenation of all routes regex, for faster matching.
	 *
	 * @return array
	 */
	protected function generateRouteRegex($data)
	{
        $regex = '';
        $parameters = [];

        foreach ($data as $part) {
            if (is_string($part)) {
                $regex .= preg_quote($part, '~');

                continue;
            }

            list($parameters_names, $regex_part) = $part;

            $parameters[$parameters_names] = $parameters_names;
            $regex .= '(' . $regex_part . ')';
        }

        return [$regex, $parameters];
	}

	/**
	 * Generate an array with all dinamic routes information.
	 *
	 * @return array
	 */
	protected function generateDinamicRouteData()
	{
		$data = [];

		foreach ($this->dinamics as $method => $routes)
		{
			$chunksize = $this->computeChunkSize(count($routes));

			$chunks = array_chunk($routes, $chunksize, true);

			$data[$method] = array_map([$this, 'computeDataChunk'], $chunks);
		}

		return $data;
	}

	/**
	 * Calculates the division size of the dinamic routes for faster matching.
	 *
	 * @return int
	 */
	protected function computeChunkSize($count)
	{
		$parts = max(1, round($count / 10));

		return ceil($count / $parts);
	}

	/**
	 * Compute the dinamic routes chunks for increase performance of regex due to dummy groups
	 * generate by self::generateRouteRegex.
	 *
	 * @return array
	 */
	protected function computeDataChunk($data)
	{
        $map = [];
        $regexes = [];
        $groupcount = 0;

        foreach ($data as $regex => $route)
        {
        	$parameterscount = count($route['parameters']);
        	$groupcount = max($groupcount, $parameterscount);

            $regexes[] = $regex . str_repeat('()', $groupcount - $parameterscount);
            $map[$groupcount + 1] = [$route['action'], $route['parameters']];

            ++$groupcount;
        }

        $regex = '~^(?|' . implode('|', $regexes) . ')$~';
        return ['regex' => $regex, 'map' => $map];
	}

	/**
	 * Dispatch method of dinamic routes, its only an helper used by the real dispatch.
	 *
	 * @return array
	 */
	protected function dispatchDinamicRoute($routes, $uri)
	{
		foreach ($routes as $data) {
			if (!preg_match($data['regex'], $uri, $matches)) {
				continue;
			}

			list($handler, $tokens) = $data['map'][count($matches)];

			$parameters = [];
			$i = 0;

			foreach ($tokens as $token) {
				$parameters[$token] = $matches[++$i];
			}

			return [
				'found' => true, 
				'action' => $handler, 
				'parameters' => $parameters
			];
		}

		return [
			'found' => false
		];
	}

}
