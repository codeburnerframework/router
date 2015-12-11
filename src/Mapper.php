<?php 

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router;

/**
 * The mapper class hold all the defined routes and give then
 * in a organized form focused to reduce the search time.
 * 
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @since 1.0.0
 */

class Mapper
{

    /**
     * Implement support to variations of the set method that abstract the
     * HTTP method from the parameters.
     */

    use HttpMethodMapper;

    /**
     * Insert support for mapping a resource.
     *
     * @see https://github.com/codeburnerframework/router/#resources
     */

    use ResourceMapper;

    /**
     * Add support to abstract a entire controller registration.
     *
     * @see https://github.com/codeburnerframework/router/#controllers
     */

    use ControllerMapper;

    /**
     * The regex used to parse all the routes patterns. For more information
     * contact the author of this class.
     *
     * @var string
     */

    const DYNAMIC_REGEX = '\{\s*([\w]*)\s*(?::\s*([^{}]*(?:\{(?-1)\}[^{}]*)*))?\s*\}';
    
    /**
     * The default pattern that will be used to match a dynamic segment of a route.
     *
     * @var string
     */

    const DEFAULT_PLACEHOLDER_REGEX = '([^/]+)';

    /**
     * All the supported http methods and they zones. An http method zone is the range
     * of numeric indexes that routes separated by the number of slashes can be registered.
     * By default the range begins on 10 and jumps 10 in every method, this give a zone
     * of infinite routes ate 9 slashes or if you prefer, segments.
     */

    const METHOD_GET    = 10;
    const METHOD_POST   = 20;
    const METHOD_PUT    = 30;
    const METHOD_PATCH  = 40;
    const METHOD_DELETE = 50;

    /**
     * An mirror for the constants values in array form for easily iteration and validation.
     * This will be deprecated soon, with the new support to array in constants of php 7 this static attribute and
     * the static getter method will be refactored to a constant.
     *
     * @var array
     */

    protected static $methods = [
        'get'    => self::METHOD_GET,
        'post'   => self::METHOD_POST,
        'put'    => self::METHOD_PUT,
        'patch'  => self::METHOD_PATCH,
        'delete' => self::METHOD_DELETE
    ];
    
    /**
     * A set of aliases to regex that can be used in patterns definitions.
     *
     * @var array
     */

    protected $patternWildcards = [
        'int' => '\d+',
        'integer' => '\d+',
        'string' => '\w+',
        'float' => '[-+]?(\d*[.])?\d+',
        'bool' => '^(1|0|true|false|yes|no)$',
        'boolean' => '^(1|0|true|false|yes|no)$'
    ];
    
    /**
     * The delimiter in the controller/method action espefication.
     *
     * @var string
     */

    protected $actionDelimiter = '#';

    /**
     * Hold all the routes without parameters.
     *
     * @var array [METHOD => [PATTERN => ROUTE]]
     */

    protected $statics;
    
    /**
     * Routes with parameters to compute.
     *
     * @var array [PROCESSED_INDEX => [ROUTE]]
     */

    protected $dynamics;

    /**
     * Insert a route into the collection.
     *
     * @param int                   $method   The HTTP method zone of route. {GET, POST, PUT, PATCH, DELETE}
     * @param string                $pattern  The URi that route should match.
     * @param string|array|\closure $action   The callback for when route is matched.
     * @param string                $strategy The route specific dispatch strategy.
     */

    public function set($method, $pattern, $action, $strategy = null)
    {
        $patterns = $this->parsePatternOptionals($pattern);
        $action = $this->parseAction($action);

        foreach ($patterns as $pattern) {
            strpos($pattern, '{') === false ?
                $this->setStatic($method, $pattern, $action, $strategy) : $this->setDynamic($method, $pattern, $action, $strategy);
        }
    }

    /**
     * Insert a static route into the collection.
     *
     * @param string                $method   The HTTP method of route. {GET, POST, PUT, PATCH, DELETE}
     * @param string                $pattern  The URi that route should match.
     * @param string|array|\closure $action   The callback for when route is matched.
     * @param string                $strategy The route specific dispatch strategy.
     */

    protected function setStatic($method, $pattern, $action, $strategy)
    {
        $this->statics[$method][$pattern] = [
            'action'   => $action,
            'params'   => [],
            'strategy' => $strategy
        ];
    }

    /**
     * Insert a dynamic route into the collection.
     *
     * @param string                $method   The HTTP method of route. {GET, POST, PUT, PATCH, DELETE}
     * @param string                $pattern  The URi that route should match.
     * @param string|array|\closure $action   The callback for when route is matched.
     * @param string                $strategy The route specific dispatch strategy.
     */

    protected function setDynamic($method, $pattern, $action, $strategy)
    {
        $index = $this->getDynamicIndex($method, $pattern);

        list($regex, $params) = $this->parsePatternPlaceholders($pattern);

        $this->dynamics[$index][] = [
            'action'   => $action,
            'regex'    => $regex,
            'params'   => $params,
            'strategy' => $strategy
        ];
    }

    /**
     * Parses the given action to something that can be called.
     *
     * @param  string|array|\closure $action  The callback for when route is matched.
     * @return string|array|\closure
     */

    protected function parseAction($action)
    {
        if (is_string($action)) {
            return explode($this->actionDelimiter, $action);
        }

        return $action;
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
        $patternOptionalsNumber = strlen($pattern) - strlen($patternWithoutClosingOptionals);

        $segments = preg_split('~' . self::DYNAMIC_REGEX . '(*SKIP)(*F) | \[~x', $patternWithoutClosingOptionals);
        $this->parseSegmentOptionals($segments, $patternOptionalsNumber, $patternWithoutClosingOptionals);

        return $this->buildPatterns($segments);
    }

    /**
     * Parse an route pattern seeking for parameters and making the route regex.
     *
     * @param string $pattern The route pattern to be parsed.
     * @return array 0 => new route regex, 1 => map of parameters names.
     */

    protected function parsePatternPlaceholders($pattern)
    {
        $parameters = [];
        preg_match_all('~' . self::DYNAMIC_REGEX . '~x', $pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ((array) $matches as $match) {
            $pattern = str_replace($match[0][0], isset($match[2]) ? '(' . trim($match[2][0]) . ')' : self::DEFAULT_PLACEHOLDER_REGEX, $pattern);
            $parameters[$match[1][0]] = $match[1][0];
        }

        return [$pattern, $parameters];
    }

    /**
     * Parse the pattern seeking for the error and show a more specific message.
     *
     * @param array $segments
     * @param int $patternOptionalsNumber
     * @param string $patternWithoutClosingOptionals
     *
     * @throws Exceptions\BadRouteException With a more specific error message.
     */

    protected function parseSegmentOptionals(array $segments, $patternOptionalsNumber, $patternWithoutClosingOptionals)
    {
        if ($patternOptionalsNumber !== count($segments) - 1) {
            if (preg_match('~' . self::DYNAMIC_REGEX . '(*SKIP)(*F) | \]~x', $patternWithoutClosingOptionals)) {
                   throw new Exceptions\BadRouteException(Exceptions\BadRouteException::OPTIONAL_SEGMENTS_ON_MIDDLE);
            } else throw new Exceptions\BadRouteException(Exceptions\BadRouteException::UNCLOSED_OPTIONAL_SEGMENTS);
        }
    }

    /**
     * Build all the possibles patterns for a set of segments.
     *
     * @param array $segments
     * @throws Exceptions\BadRouteException
     * @return array
     */

    protected function buildPatterns(array $segments)
    {
        $pattern  = '';
        $patterns = [];

        foreach ($segments as $n => $segment) {
            if ($segment === '' && $n !== 0) {
                throw new Exceptions\BadRouteException(Exceptions\BadRouteException::EMPTY_OPTIONAL_PARTS);
            }

            $patterns[] = $pattern .= $segment;
        }

        return $patterns;
    }

    /**
     * Group several routes into one unique regex.
     *
     * @param  array $routes All the routes that must be grouped
     * @return array
     */

    protected function buildGroup($routes)
    {
        $map = []; 
        $regex = [];
        $groupCount = 0;

        foreach ($routes as $route) {
            $paramsCount      = count($route['params']);
            $groupCount       = max($groupCount, $paramsCount) + 1;
            $regex[]          = $route['regex'] . str_repeat('()', $groupCount - $paramsCount - 1);
            $map[$groupCount] = [$route['action'], $route['params'], $route['strategy']];
        }

        return ['regex' => '~^(?|' . implode('|', $regex) . ')$~', 'map' => $map];
    }

    /**
     * Generate an index that will hold an especific group of dynamic routes.
     *
     * @param int $method The http method index defined by the METHOD_* constants
     * @param string $pattern
     *
     * @return int
     */

    protected function getDynamicIndex($method, $pattern)
    {
        return (int) $method + substr_count($pattern, '/') - 1;
    }

    /**
     * Retrieve a specific static route or a false.
     *
     * @param int $method The http method index defined by the METHOD_* constants
     * @param string $pattern
     *
     * @return array|false
     */

    public function getStaticRoute($method, $pattern)
    {
        if (isset($this->statics[$method]) && isset($this->statics[$method][$pattern])) {
            return $this->statics[$method][$pattern];
        }

        return false;
    }

    /**
     * Concat all dynamic routes regex for a given method, this speeds up the match.
     *
     * @param string $method The http method to search in.
     * @param string $pattern
     *
     * @return array [['regex', 'map' => [0 => action, 1 => params]]]
     */

    public function getDynamicRoutes($method, $pattern)
    {
        $index = $this->getDynamicIndex($method, $pattern);

        if (isset($this->dynamics[0])) {
               $dynamics = $this->dynamics[0];
        } else $dynamics = [];

        if (!isset($this->dynamics[$index])) {
            return $dynamics;
        }

        $dynamics = array_merge($dynamics, $this->dynamics[$index]);
        $chunks   = array_chunk($dynamics, round(1 + 3.3 * log(count($dynamics))), true);

        return array_map([$this, 'buildGroup'], $chunks);
    }

    /**
     * @return array
     */
    public static function getMethods()
    {
        return self::$methods;
    }

    /**
     * @return array
     */
    public function getPatternWildcards()
    {
        return $this->patternWildcards;
    }

    /**
     * @param string $pattern
     * @param string $wildcard
     */
    public function setPatternWildcard($pattern, $wildcard)
    {
        $this->patternWildcards[(string) $pattern] = (string) $wildcard;
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

}

/**
 * Give the mapper methods that abstract the first parameter relative to
 * HTTP methods into new mapper methods.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @since 1.0.0
 */

trait HttpMethodMapper
{

    abstract public function set($method, $pattern, $action, $strategy = null);

    /**
     * Register a set of routes for they especific http methods.
     *
     * @param string                $pattern  The URi pattern that should be matched.
     * @param string|array|\closure $action   The action that must be executed in case of match.
     * @param string                $strategy The route specific dispatch strategy.
     */

    public function get($pattern, $action, $strategy = null)
    {
        $this->set(Mapper::METHOD_GET, $pattern, $action, $strategy);
    }

    public function post($pattern, $action, $strategy = null)
    {
        $this->set(Mapper::METHOD_POST, $pattern, $action, $strategy);
    }

    public function put($pattern, $action, $strategy = null)
    {
        $this->set(Mapper::METHOD_PUT, $pattern, $action, $strategy);
    }

    public function patch($pattern, $action, $strategy = null)
    {
        $this->set(Mapper::METHOD_PATCH, $pattern, $action, $strategy);
    }

    public function delete($pattern, $action, $strategy = null)
    {
        $this->set(Mapper::METHOD_DELETE, $pattern, $action, $strategy);
    }

    /**
     * Register a route into all HTTP methods.
     *
     * @param string                $pattern  The URi pattern that should be matched.
     * @param string|array|\closure $action   The action that must be executed in case of match.
     * @param string                $strategy The route specific dispatch strategy.
     */
    public function any($pattern, $action, $strategy = null)
    {
        foreach (Mapper::getMethods() as $method) {
            $this->set($method, $pattern, $action, $strategy);
        }
    }

    /**
     * Register a route into all HTTP methods except by $method.
     *
     * @param string                $methods  The method that must be excluded.
     * @param string                $pattern  The URi pattern that should be matched.
     * @param string|array|\closure $action   The action that must be executed in case of match.
     * @param string                $strategy The route specific dispatch strategy.
     */
    public function except($methods, $pattern, $action, $strategy = null)
    {
        foreach (array_diff_key(Mapper::getMethods(), array_flip((array) $methods)) as $method) {
            $this->set($method, $pattern, $action, $strategy);
        }
    }

    /**
     * Register a route into given HTTP method(s).
     *
     * @param string|array          $methods  The method that must be matched.
     * @param string                $pattern  The URi pattern that should be matched.
     * @param string|array|\closure $action   The action that must be executed in case of match.
     * @param string                $strategy The route specific dispatch strategy.
     */
    public function match($methods, $pattern, $action, $strategy = null)
    {
        foreach (array_intersect_key(Mapper::getMethods(), array_flip((array) $methods)) as $method) {
            $this->set($method, $pattern, $action, $strategy);
        }
    }

}

/**
 * Make mapper aware of the controllers, give to it a controller method that will
 * map all the controllers public methods that begins with an HTTP method.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @since 1.0.0
 */

trait ControllerMapper
{

    abstract public function getActionDelimiter();
    abstract public function getPatternWildcards();
    abstract public function match($methods, $pattern, $action, $strategy = null);

    /**
     * Maps all the controller methods that begins with a HTTP method, and maps the rest of
     * name as a uri. The uri will be the method name with slashes before every camelcased 
     * word and without the HTTP method prefix. 
     * e.g. getSomePage will generate a route to: GET some/page
     *
     * @param string|object $controller The controller name or representation.
     * @param bool          $prefix     Dict if the controller name should prefix the path.
     *
     * @throws \BadMethodCallException
     * @return Mapper
     */

    public function controller($controller, $prefix = true)
    {
        if (!$methods = get_class_methods($controller)) {
            throw new \BadMethodCallException('The controller class could not be inspected.');
        }

        $methods = $this->getControllerMethods($methods);
        $prefix = $this->getControllerPrefix($prefix, $controller);
        $delimiter = $this->getActionDelimiter();

        foreach ($methods as $route) {
            $uri = preg_replace_callback('~(^|[a-z])([A-Z])~', [$this, 'getControllerAction'], $route[1]);

            $methodName = $route[0] . $route[1];
            $methodObj = new \ReflectionMethod($controller, $methodName);
            $dynamic = $this->getMethodConstraints($methodObj);

            $this->{$route[0]}($prefix . $uri . $dynamic, $controller . $delimiter . $methodName, $this->getMethodStrategy($methodObj));
        }

        return $this;
    }

    /**
     * Give a prefix for the controller routes paths.
     *
     * @param bool $prefix Must prefix?
     * @param string|object $controller The controller name or representation.
     *
     * @return string
     */

    protected function getControllerPrefix($prefix, $controller)
    {
        $path = '/';

        if ($prefix === true) {
            $path .= $this->getControllerName($controller);
        }

        return $path;
    }

    /**
     * Transform camelcased strings into URIs.
     *
     * @param array $matches
     *
     * @return string
     */

    public function getControllerAction(array $matches)
    {
        return strtolower(strlen($matches[1]) ? $matches[1] . '/' . $matches[2] : $matches[2]);
    }

    /**
     * Get the controller name without the suffix Controller.
     *
     * @param string|object $controller
     * @param array $options
     *
     * @return string
     */

    public function getControllerName($controller, array $options = array())
    {
        if (isset($options['as'])) {
            return $options['as'];
        }

        if (is_object($controller)) {
            $controller = get_class($controller);
        }

        return strtolower(strstr(array_reverse(explode('\\', $controller))[0], 'Controller', true));
    }

    /**
     * Maps the controller methods to HTTP methods.
     *
     * @param array $classMethods All the controller public methods
     * @return array An array keyed by HTTP methods and their controller methods.
     */

    protected function getControllerMethods($classMethods)
    {
        $mapMethods = [];
        $httpMethods = array_keys(Mapper::getMethods());

        foreach ($classMethods as $classMethod) {
            foreach ($httpMethods as $httpMethod) {
                if (strpos($classMethod, $httpMethod) === 0) {
                    $mapMethods[] = [$httpMethod, substr($classMethod, strlen($httpMethod))];
                }
            }
        }

        return $mapMethods;
    }

    /**
     * Inspect a method seeking for parameters and make a dynamic pattern.
     *
     * @param string|object $controller The controller representation.
     * @param string        $method     The method to be inspected name.
     *
     * @return string The resulting URi.
     */

    protected function getMethodConstraints(\ReflectionMethod $method)
    {
        $beginUri = '';
        $endUri = '';

        if ($parameters = $method->getParameters()) {
            $types = $this->getParamsConstraint($method);

            foreach ($parameters as $parameter) {
                if ($parameter->isOptional()) {
                    $beginUri .= '[';
                    $endUri .= ']';
                }

                $beginUri .= $this->getUriConstraint($parameter, $types);
            }
        }

        return $beginUri . $endUri;
    }

    /**
     * Return a URi segment based on parameters constraints.
     *
     * @param \ReflectionParameter $parameter The parameter base to build the constraint.
     * @param array $types All the parsed constraints.
     *
     * @return string
     */

    protected function getUriConstraint(\ReflectionParameter $parameter, $types)
    {
        $name = $parameter->name;
        $uri  = '/{' . $name;

        if (isset($types[$name])) {
            return  $uri . ':' . $types[$name] . '}';
        } else {
            return $uri . '}';
        }
    }

    /**
     * Get all parameters with they constraint.
     *
     * @param \ReflectionMethod $method The method to be inspected name.
     * @return array All the parameters with they constraint.
     */

    protected function getParamsConstraint(\ReflectionMethod $method)
    {
        $params = [];
        preg_match_all('~\@param\s(' . implode('|', array_keys($this->getPatternWildcards())) . ')\s\$([a-zA-Z]+)\s(Match \((.+)\))?~',
            $method->getDocComment(), $types, PREG_SET_ORDER);

        foreach ((array) $types as $type) {
            $params[$type[2]] = $this->getParamConstraint($type);
        }

        return $params;
    }

    /**
     * Convert PHPDoc type to a constraint.
     *
     * @param string $type The PHPDoc type.
     * @return string The Constraint string.
     */

    protected function getParamConstraint($type)
    {
        if (isset($type[4])) {
            return $type[4];
        }

        return $this->getPatternWildcards()[$type[1]];
    }

    /**
     * Get the strategy defined for a controller method by comment.
     *
     * @param \ReflectionMethod $method
     * @return null|string
     */

    protected function getMethodStrategy(\ReflectionMethod $method)
    {
        preg_match('~\@strategy\s([a-zA-Z\\\_]+)~', $method->getDocComment(), $strategy);
        return isset($strategy[1]) ? $strategy[1] : null;
    }

}

/**
 * Enable mapper to be more RESTFul, registering 7 routes at same time for all the CRUD operations.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @since 1.0.0
 */

trait ResourceMapper
{

    abstract public function set($methods, $pattern, $action, $strategy = null);
    abstract public function getActionDelimiter();
    abstract public function getControllerName($controller, array $options = array());

    /**
     * A map of all routes of resources.
     *
     * @var array
     */

    protected $map = [
        'index' => [Mapper::METHOD_GET, '/:name'],
        'make' => [Mapper::METHOD_GET, '/:name/make'],
        'create' => [Mapper::METHOD_POST, '/:name'],
        'show' => [Mapper::METHOD_GET, '/:name/{id}'],
        'edit' => [Mapper::METHOD_GET, '/:name/{id}/edit'],
        'update' => [Mapper::METHOD_PUT, '/:name/{id}'],
        'delete' => [Mapper::METHOD_DELETE, '/:name/{id}'],
    ];

    /**
     * Resource routing allows you to quickly declare all of the common routes for a given resourceful controller. 
     * Instead of declaring separate routes for your index, show, new, edit, create, update and destroy actions, 
     * a resourceful route declares them in a single line of code
     *
     * @param string|object $controller The controller name or representation.
     * @param array         $options    Some options like, 'as' to name the route pattern, 'only' to
     *                                  explicit say that only this routes will be registered, and
     *                                  except that register all the routes except the indicates.
     */

    public function resource($controller, array $options = array())
    {
        $name  = isset($options['prefix']) ? $options['prefix'] : '';
        $name .= $this->getControllerName($controller, $options);
        $actions = $this->getResourceActions($options);
        $delimiter = $this->getActionDelimiter();

        foreach ($actions as $action => $map) {
            $this->set($map[0], str_replace(':name', $name, $map[1]),
                is_string($controller) ? $controller . $delimiter . $action : [$controller, $action]);
        }
    }

    /**
     * Parse the options to find out what actions will be registered.
     *
     * @param array $options
     * @return array
     */

    protected function getResourceActions($options)
    {
        if (isset($options['only'])) {
            return array_intersect_key($this->map, array_flip($options['only']));
        }

        if (isset($options['except'])) {
            return array_diff_key($this->map, array_flip($options['except']));
        }

        return $this->map;
    }

}
