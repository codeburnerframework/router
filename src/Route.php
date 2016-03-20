<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2016 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router;

use Codeburner\Router\Exceptions\BadRouteException;
use Codeburner\Router\Strategies\{MatcherAwareInterface, StrategyInterface};

/**
 * Route representation, a route must be able to chang and execute itself.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class Route
{

    /**
     * @var Collector
     */

    protected $collector;

    /**
     * @var string
     */

    protected $method;

    /**
     * @var string
     */

    protected $pattern;

    /**
     * @var callable
     */

    protected $action;

    /**
     * @var string
     */

    protected $namespace = "";

    /**
     * @var string[]
     */

    protected $params = [];

    /**
     * Defaults are parameters set by the user, and don't
     * appear on the pattern.
     *
     * @var array
     */

    protected $defaults = [];

    /**
     * Metadata can be set to be used on filters, dispatch strategies
     * or anywhere the route object is used.
     *
     * @var array
     */

    protected $metadata = [];

    /**
     * @var string|StrategyInterface
     */

    protected $strategy;

    /**
     * Blocked routes are dynamic routes selected to pass by the matcher.
     *
     * @var boolean
     */

    protected $blocked = false;

    /**
     * The matcher that dispatched this route.
     *
     * @var Matcher $matcher
     */

    protected $matcher;

    /**
     * Route name, or alias.
     *
     * @var string $name
     */

    protected $name;

    /**
     * The function used to create controllers from name.
     *
     * @var callable
     */

    protected $controllerCreationFunction;

    /**
     * @param Collector $collector
     * @param string $method
     * @param string $pattern
     * @param callable $action
     */

    public function __construct(Collector $collector, string $method, string $pattern, $action)
    {
        $this->collector = $collector;
        $this->method    = $method;
        $this->pattern   = $pattern;
        $this->action    = $action;
    }

    /**
     * Clone this route and set it into the collector.
     *
     * @return Route
     */

    public function reset() : Route
    {
        return $this->collector->set($this->method, $this->pattern, $this->action)->nth(0)
                               ->setStrategy($this->strategy)->setParams($this->params)
                               ->setDefaults($this->defaults)->setMetadataArray($this->metadata);
    }

    /**
     * Remove this route from the collector.
     *
     * @return self
     */

    public function forget() : self
    {
        $this->collector->forget($this->method, $this->pattern);
        return $this;
    }

    /**
     * Execute the route action, if no strategy was provided the action
     * will be executed by the call_user_func PHP function.
     *
     * @param callable $container
     * @throws BadRouteException
     * @return mixed
     */

    public function call(callable $container = null)
    {
        $this->action = $this->buildCallable($this->action, $container);

        if ($this->strategy === null) {
            return call_user_func_array($this->action, array_merge($this->defaults, $this->params));
        }

        if (!is_object($this->strategy)) {
            if ($container === null) {
                   $this->strategy = new $this->strategy;
            } else $this->strategy = $container($this->strategy);
        }

        return $this->callWithStrategy();
    }

    /**
     * Seek for dynamic content in one callable. This allow to use parameters defined on pattern on callable
     * definition, eg. "get" "/{resource:string+}/{slug:slug+}" "{resource}::find".
     *
     * This will snakecase the resource parameter and deal with as a controller, then call the find method.
     * A request for "/articles/my-first-article" will execute find method of Articles controller with only
     * "my-first-article" as parameter.
     *
     * @param callable $callable
     * @param callable $container
     *
     * @return callable
     */

    private function buildCallable($callable, $container = null)
    {
        if (is_string($callable) && strpos($callable, "::")) {
            $callable = explode("::", $callable);
        }

        if (is_array($callable)) {
            if (is_string($callable[0])) {
                   $callable[0] = $this->parseCallableController($callable[0], $container);
            }

            $callable[1] = $this->parseCallablePlaceholders($callable[1]);
        }

        return $callable;
    }

    /**
     * Get the controller object.
     *
     * @param string $controller
     * @param callable $container
     *
     * @return Object
     */

    private function parseCallableController(string $controller, $container)
    {
        $controller  = rtrim($this->namespace, "\\") . "\\" . $this->parseCallablePlaceholders($controller);

        if ($container === null) {
               return new $controller;
        } else return $container($controller);
    }

    /**
     * Parse and replace dynamic content on route action.
     *
     * @param string $fragment Part of callable
     * @return string
     */

    private function parseCallablePlaceholders(string $fragment) : string
    {
        if (strpos($fragment, "{") !== false) {
            foreach ($this->params as $placeholder => $value) {
                if (strpos($fragment, "{" . $placeholder . "}") !== false) {
                    $fragment = str_replace("{" . $placeholder . "}", ucwords(str_replace("-", " ", $value)), $fragment);
                }
            }
        }

        return $fragment;
    }

    /**
     * Execute the route action with the given strategy.
     *
     * @throws BadRouteException
     * @return mixed
     */

    private function callWithStrategy()
    {
        if ($this->strategy instanceof StrategyInterface) {
            if ($this->strategy instanceof MatcherAwareInterface) {
                $this->strategy->setMatcher($this->matcher);
            }

            return $this->strategy->call($this);
        }

        throw new BadRouteException(str_replace("%s", get_class($this->strategy), BadRouteException::BAD_STRATEGY));
    }

    /**
     * @return Collector
     */

    public function getCollector() : Collector
    {
        return $this->collector;
    }

    /**
     * @return string
     */

    public function getMethod() : string
    {
        return $this->method;
    }

    /**
     * @return string
     */

    public function getPattern() : string
    {
        return $this->pattern;
    }

    /**
     * @return string[]
     */

    public function getSegments() : array
    {
        return explode("/", $this->pattern);
    }

    /**
     * @return callable
     */

    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return string
     */

    public function getNamespace() : string
    {
        return $this->namespace;
    }

    /**
     * @return string[]
     */

    public function getParam(string $key = "")
    {
        return $key ? (isset($this->params[$key]) ? $this->params[$key] : null) : $this->params;
    }

    /**
     * Return defaults and params merged in one array.
     *
     * @return array
     */

    public function getMergedParams() : array
    {
        return array_merge($this->defaults, $this->params);
    }

    /**
     * @return array
     */

    public function getDefault(string $key = "")
    {
        return $key ? (isset($this->defaults[$key]) ? $this->defaults[$key] : null) : $this->defaults;
    }

    /**
     * @param string $key
     * @return mixed
     */

    public function getMetadata(string $key = "")
    {
        return $key ? (isset($this->metadata[$key]) ? $this->metadata[$key] : null) : $this->metadata;
    }

    /**
     * @return string
     */

    public function getStrategy() : string
    {
        if ($this->strategy instanceof StrategyInterface) {
            return get_class($this->strategy);
        }

        return (string) $this->strategy;
    }

    /**
     * @return StrategyInterface|string
     */

    public function getRawStrategy()
    {
        return $this->strategy;
    }

    /**
     * @return Matcher
     */

    public function getMatcher() : Matcher
    {
        return $this->matcher;
    }

    /**
     * @return string
     */

    public function getName() : string
    {
        return $this->name;
    }

    /**
     * Verify if a Route have already been blocked.
     *
     * @return boolean
     */

    public function getBlock() : bool
    {
        return $this->blocked;
    }

    /**
     * Blocking a route indicate that that route have been selected and
     * parsed, now it will be given to the matcher.
     *
     * @param bool $blocked
     * @return self
     */

    public function setBlock(bool $blocked) : self
    {
        $this->blocked = $blocked;
        return $this;
    }

    /**
     * @param string $method
     * @return Route
     */

    public function setMethod(string $method) : Route
    {
        $this->forget();
        $this->method = $method;
        return $this->reset();
    }

    /**
     * @param string $pattern
     * @return Route
     */

    public function setPattern(string $pattern) : Route
    {
        $this->forget();
        $this->pattern = $pattern;
        return $this->reset();
    }

    /**
     * @param string $pattern
     * @return self
     */

    public function setPatternWithoutReset(string $pattern) : self
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * @param callable $action
     * @return self
     */

    public function setAction($action) : self
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @param string $namespace
     * @return self
     */

    public function setNamespace(string $namespace) : self
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @param string[] $params
     * @return self
     */

    public function setParams(array $params) : self
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return self
     */

    public function setParam(string $key, string $value) : self
    {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * @param mixed[] $defaults
     * @return self
     */

    public function setDefaults(array $defaults) : self
    {
        $this->defaults = $defaults;
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return self
     */

    public function setDefault(string $key, $value) : self
    {
        $this->defaults[$key] = $value;
        return $this;
    }

    /**
     * @param mixed[] $metadata
     * @return self
     */

    public function setMetadataArray(array $metadata) : self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return self
     */

    public function setMetadata(string $key, $value) : self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * @param null|string|StrategyInterface $strategy
     * @return self
     */

    public function setStrategy($strategy) : self
    {
        $this->strategy = $strategy;
        return $this;
    }

    /**
     * @param Matcher $matcher
     * @return self
     */

    public function setMatcher(Matcher $matcher) : self
    {
        $this->matcher = $matcher;
        return $this;
    }

    /**
     * @param string $name
     * @return self
     */

    public function setName(string $name) : self
    {
        $this->name = $name;
        $this->collector->setRouteName($name, $this);
        return $this;
    }

    /**
     * Set a constraint to a token in the route pattern.
     *
     * @param string $token
     * @param string $regex
     *
     * @return self
     */

    public function setConstraint(string $token, string $regex) : self
    {
        $initPos = strpos($this->pattern, "{" . $token);

        if ($initPos !== false) {
            $endPos = strpos($this->pattern, "}", $initPos);
            $newPattern = substr_replace($this->pattern, "{" . "$token:$regex" . "}", $initPos, $endPos - $initPos + 1);
            $wildcards = $this->collector->getParser()->getWildcardTokens();
            $newPattern = str_replace(array_keys($wildcards), $wildcards, $newPattern);
            $this->setPatternWithoutReset($newPattern);
        }

        return $this;
    }

    /**
     * @param string $key
     * @return bool
     */

    public function hasParam(string $key) : bool
    {
        return isset($this->params[$key]);
    }

    /**
     * @param string $key
     * @return bool
     */

    public function hasDefault(string $key) : bool
    {
        return isset($this->defaults[$key]);
    }

    /**
     * @param string $key
     * @return bool
     */

    public function hasMetadata(string $key) : bool
    {
        return isset($this->metadata[$key]);
    }

}
