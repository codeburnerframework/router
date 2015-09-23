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
 * The mapper class is reponsable to hold all the defined routes and give then
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

    const DINAMIC_REGEX = '\{\s*([\w]*)\s*(?::\s*([^{}]*(?:\{(?-1)\}[^{}]*)*))?\s*\}';
    
    /**
     * The default pattern that will be used to match a dinamic segment of a route.
     *
     * @var string
     */

    const DEFAULT_PLACEHOLD_REGEX = '([^/]+)';

    /**
     * All the supported http methods and they zones. An http method zone is the range
     * of numeric indexes that routes separated by the number of slashes can be registered.
     * By default the range begins on 10 and jumps 10 in every method, this give a zone
     * of infinite routes ate 9 slashes or if you prefer, segments.
     * 
     * @var array
     */

    public static $methods = [
        'get' => 10,
        'post' => 20,
        'put' => 30,
        'patch' => 40,
        'delete' => 50
    ];
    
    /**
     * A set of aliases to regex that can be used in patterns definitions.
     *
     * @var array
     */

    public static $pattern_wildcards = [
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

    public static $action_delimiter = '#';

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

    protected $dinamics;

    /**
     * Insert a route into the collection.
     *
     * @param int                   $method  The HTTP method zone of route. {GET, POST, PUT, PATCH, DELETE}
     * @param string                $pattern The URi that route should match.
     * @param string|array|\closure $action  The callback for when route is matched.
     */

    public function set($method, $pattern, $action)
    {
        $patterns = $this->parsePatternOptionals($pattern);
        $action = $this->parseAction($action);

        foreach ($patterns as $pattern) {
            strpos($pattern, '{') === false ?
                $this->setStatic($method, $pattern, $action) : $this->setDinamic($method, $pattern, $action);
        }
    }

    /**
     * Insert a static route into the collection.
     *
     * @param string                $method  The HTTP method of route. {GET, POST, PUT, PATCH, DELETE}
     * @param string                $pattern The URi that route should match.
     * @param string|array|\closure $action  The callback for when route is matched.
     */

    protected function setStatic($method, $pattern, $action)
    {
        $this->statics[$method][$pattern] = [
            'action' => $action,
            'params' => []
        ];
    }

    /**
     * Insert a dinamic route into the collection.
     *
     * @param string                $method  The HTTP method of route. {GET, POST, PUT, PATCH, DELETE}
     * @param string                $pattern The URi that route should match.
     * @param string|array|\closure $action  The callback for when route is matched.
     */

    protected function setDinamic($method, $pattern, $action)
    {
        $index = $this->getDinamicIndex($method, $pattern);

        list($regex, $params) = $this->parsePatternPlaceholders($pattern);

        $this->dinamics[$index][] = [
            'action'  => $action,
            'regex' => $regex,
            'params'  => $params
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
            return explode(self::$action_delimiter, $action);
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

        $segments = preg_split('~' . self::DINAMIC_REGEX . '(*SKIP)(*F) | \[~x', $patternWithoutClosingOptionals);
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
        preg_match_all('~' . self::DINAMIC_REGEX . '~x', $pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ((array) $matches as $match) {
            $pattern = str_replace($match[0][0], isset($match[2]) ? '(' . trim($match[2][0]) . ')' : self::DEFAULT_PLACEHOLD_REGEX, $pattern);
            $parameters[$match[1][0]] = $match[1][0];
        }

        return [$pattern, $parameters];
    }

    /**
     * Parse the pattern seeking for the error and show a more specific message.
     *
     * @throws \Exception With a more specific error message.
     */

    protected function parseSegmentOptionals($segments, $patternOptionalsNumber, $patternWithoutClosingOptionals)
    {
        if ($patternOptionalsNumber !== count($segments) - 1) {
            if (preg_match('~' . self::DINAMIC_REGEX . '(*SKIP)(*F) | \]~x', $patternWithoutClosingOptionals)) {
                   throw new Exceptions\BadRouteException(Exceptions\BadRouteException::OPTIONAL_SEGMENTS_ON_MIDDLE);
            } else throw new Exceptions\BadRouteException(Exceptions\BadRouteException::UNCLOSED_OPTIONAL_SEGMENTS);
        }
    }

    /**
     * Build all the possibles patterns for a set of segments.
     *
     * @throws Exceptions\BadRouteException
     * @return array
     */

    protected function buildPatterns($segments)
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
        $regexes = []; 
        $groupcount = 0;

        foreach ($routes as $route) {
            $paramscount      = count($route['params']);
            $groupcount       = max($groupcount, $paramscount) + 1;
            $regexes[]        = $route['regex'] . str_repeat('()', $groupcount - $paramscount - 1);
            $map[$groupcount] = [$route['action'], $route['params']];
        }

        return ['regex' => '~^(?|' . implode('|', $regexes) . ')$~', 'map' => $map];
    }

    /**
     * Generate an index that will hold an especific group of dinamic routes.
     *
     * @return int
     */

    protected function getDinamicIndex($method, $pattern)
    {
        return (int) $method + substr_count($pattern, '/') - 1;
    }

    /**
     * Retrieve a specific static route or a false.
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
     * Concat all dinamic routes regex for a given method, this speeds up the match.
     *
     * @param string $method The http method to search in.
     * @return array [['regex', 'map' => [0 => action, 1 => params]]]
     */

    public function getDinamicRoutes($method, $pattern)
    {
        $index = $this->getDinamicIndex($method, $pattern);

        if (isset($this->dinamics[0])) {
               $dinamics = $this->dinamics[0];
        } else $dinamics = [];

        if (!isset($this->dinamics[$index])) {
            return $dinamics;
        }

        $dinamics = array_merge($dinamics, $this->dinamics[$index]);
        $chunks   = array_chunk($dinamics, round(1 + 3.3 * log(count($dinamics))), true);

        return array_map([$this, 'buildGroup'], $chunks);
    }

    /**
     * Set a new Delimiter for the routes actions.
     *
     * @param string $delimiter
     */

    public function setActionDelimiter($delimiter)
    {
        self::$action_delimiter = (string) $delimiter;
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

    abstract public function set($method, $pattern, $action);

    /**
     * Register a set of routes for they especific http methods.
     *
     * @param string                $pattern  The URi pattern that should be matched.
     * @param string|array|\closure $action   The action that must be executed in case of match.
     */

    public function get($pattern, $action)
    {
        $this->set(self::$methods['get'], $pattern, $action);
    }

    public function post($pattern, $action)
    {
        $this->set(self::$methods['post'], $pattern, $action);
    }

    public function put($pattern, $action)
    {
        $this->set(self::$methods['put'], $pattern, $action);
    }

    public function patch($pattern, $action)
    {
        $this->set(self::$methods['patch'], $pattern, $action);
    }

    public function delete($pattern, $action)
    {
        $this->set(self::$methods['delete'], $pattern, $action);
    }

    /**
     * Register a route into all HTTP methods.
     *
     * @param string                $pattern  The URi pattern that should be matched.
     * @param string|array|\closure $action   The action that must be executed in case of match.
     */
    public function any($pattern, $action)
    {
        foreach (self::$methods as $method) {
            $this->set($method, $pattern, $action);
        }

        return $this;
    }

    /**
     * Register a route into all HTTP methods except by $method.
     *
     * @param string                $methods  The method that must be excluded.
     * @param string                $pattern  The URi pattern that should be matched.
     * @param string|array|\closure $action   The action that must be executed in case of match.
     */
    public function except($methods, $pattern, $action)
    {
        foreach (array_diff_key(self::$methods, array_flip((array) $methods)) as $method) {
            $this->set($method, $pattern, $action);
        }

        return $this;
    }

    /**
     * Register a route into given HTTP method(s).
     *
     * @param string|array          $methods  The method that must be matched.
     * @param string                $pattern  The URi pattern that should be matched.
     * @param string|array|\closure $action   The action that must be executed in case of match.
     */
    public function match($methods, $pattern, $action)
    {
        foreach (array_intersect_key(self::$methods, array_flip((array) $methods)) as $method) {
            $this->set($method, $pattern, $action);
        }

        return $this;
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
    
    abstract public function match($methods, $pattern, $action);

    /**
     * Maps all the controller methods that begins with a HTTP method, and maps the rest of
     * name as a uri. The uri will be the method name with slashes before every camelcased 
     * word and without the HTTP method prefix. 
     * e.g. getSomePage will generate a route to: GET some/page
     *
     * @param string|object $controller The controller name or representation.
     * @param bool          $prefix     Dict if the controller name should prefix the path.
     *
     * @throws \Exception
     * @return Mapper
     */

    public function controller($controller, $prefix = true)
    {
        if (!$methods = get_class_methods($controller)) {
            throw new \Exception('The controller class coul\'d not be inspected.');
        }

        $methods = $this->getControllerMethods($methods);
        $prefix = $this->getControllerPrefix($prefix, $controller);

        foreach ($methods as $route) {
            $uri = preg_replace_callback('~(^|[a-z])([A-Z])~', [$this, 'getControllerAction'], $route[1]);

            $method  = $route[0] . $route[1];
            $dinamic = $this->getMethodConstraints($controller, $method);

            $this->match($route[0], $prefix . "$uri$dinamic", $controller . self::$action_delimiter . $method);
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
     * @return string
     */

    public function getControllerAction($matches)
    {
        return strtolower(strlen($matches[1]) ? $matches[1] . '/' . $matches[2] : $matches[2]);
    }

    /**
     * Get the controller name without the suffix Controller.
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
     * @param array $methods All the controller public methods
     * @return array An array keyed by HTTP methods and their controller methods.
     */

    protected function getControllerMethods($methods)
    {
        $mapmethods = [];
        $httpmethods = array_keys(self::$methods);

        foreach ($methods as $classmethod) {
            foreach ($httpmethods as $httpmethod) {
                if (strpos($classmethod, $httpmethod) === 0) {
                    $mapmethods[] = [$httpmethod, substr($classmethod, strlen($httpmethod))];
                }
            }
        }

        return $mapmethods;
    }

    /**
     * Inspect a method seeking for parameters and make a dinamic pattern.
     *
     * @param string|object $controller The controller representation.
     * @param string        $method     The method to be inspected name.
     *
     * @return string The resulting URi.
     */

    protected function getMethodConstraints($controller, $method)
    {
        $method = new \ReflectionMethod($controller, $method);
        $buri  = '';
        $euri  = '';

        foreach ((array) $method->getParameters() as $parameter) {
            if ($parameter->isOptional()) {
                $buri .= '[';
                $euri .= ']';
            }

            $buri .= $this->getUriConstraint($parameter, $types);
        }

        return $buri . $euri;
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
        preg_match_all('~\@param\s(' . implode('|', array_keys(self::$pattern_wildcards)) . ')\s\$([a-zA-Z]+)\s(Match \((.+)\))?~', 
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

        return self::$pattern_wildcards[$type[1]];
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

    abstract public function set($methods, $pattern, $action);
    abstract public function getControllerName($controller, array $options = array());

    /**
     * A map of all routes of resources.
     *
     * @var array
     */

    protected $map = [
        'index' => ['get', '/:name'],
        'make' => ['get', '/:name/make'],
        'create' => ['post', '/:name'],
        'show' => ['get', '/:name/{id}'],
        'edit' => ['get', '/:name/{id}/edit'],
        'update' => ['put', '/:name/{id}'],
        'delete' => ['delete', '/:name/{id}'],
    ];

    /**
     * Resource routing allows you to quickly declare all of the common routes for a given resourceful controller. 
     * Instead of declaring separate routes for your index, show, new, edit, create, update and destroy actions, 
     * a resourceful route declares them in a single line of code
     *
     * @param string|object $controller The controller name or representation.
     * @param array         $options    Some options like, 'as' to name the route pattern, 'only' to
     *                                  explicty say that only this routes will be registered, and 
     *                                  except that register all the routes except the indicates.
     */

    public function resource($controller, array $options = array())
    {
        $name  = isset($options['prefix']) ? $options['prefix'] : '';
        $name .= $this->getControllerName($controller, $options);
        $actions = $this->getResourceActions($options);

        foreach ($actions as $action => $map) {
            $this->set(self::$methods[$map[0]], str_replace(':name', $name, $map[1]), 
                is_string($controller) ? $controller . self::$action_delimiter . $action : [$controller, $action]);
        }
    }

    /**
     * Parse the options to find out what actions will be registered.
     *
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
