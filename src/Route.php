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
use Codeburner\Router\Strategies\MatcherAwareInterface;
use Codeburner\Router\Strategies\StrategyInterface;

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

    public function __construct(Collector $collector, $method, $pattern, $action)
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

    public function reset()
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

    public function forget()
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

    private function parseCallableController($controller, $container)
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

    private function parseCallablePlaceholders($fragment)
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

    public function getCollector()
    {
        return $this->collector;
    }

    /**
     * @return string
     */

    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */

    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @return string[]
     */

    public function getSegments()
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

    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return string[]
     */

    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param string $key
     * @return string
     */

    public function getParam($key)
    {
        return $this->params[$key];
    }

    /**
     * Return defaults and params merged in one array.
     *
     * @return array
     */

    public function getMergedParams()
    {
        return array_merge($this->defaults, $this->params);
    }

    /**
     * @return array
     */

    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * @param string $key
     * @return mixed
     */

    public function getDefault($key)
    {
        return $this->defaults[$key];
    }

    /**
     * @return array
     */

    public function getMetadataArray()
    {
        return $this->metadata;
    }

    /**
     * @param string $key
     * @return mixed
     */

    public function getMetadata($key)
    {
        return $this->metadata[$key];
    }

    /**
     * @return string|null
     */

    public function getStrategy()
    {
        if ($this->strategy instanceof StrategyInterface) {
            return get_class($this->strategy);
        }

        return $this->strategy;
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

    public function getMatcher()
    {
        return $this->matcher;
    }

    /**
     * @return string
     */

    public function getName()
    {
        return $this->name;
    }

    /**
     * Verify if a Route have already been blocked.
     *
     * @return boolean
     */

    public function getBlock()
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

    public function setBlock($blocked)
    {
        $this->blocked = $blocked;
        return $this;
    }

    /**
     * @param string $method
     * @return Route
     */

    public function setMethod($method)
    {
        $this->forget();
        $this->method = $method;
        return $this->reset();
    }

    /**
     * @param string $pattern
     * @return Route
     */

    public function setPattern($pattern)
    {
        $this->forget();
        $this->pattern = $pattern;
        return $this->reset();
    }

    /**
     * @param string $pattern
     * @return self
     */

    public function setPatternWithoutReset($pattern)
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * @param string $action
     * @return self
     */

    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @param string $namespace
     * @return self
     */

    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @param string[] $params
     * @return self
     */

    public function setParams(array $params)
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

    public function setParam($key, $value)
    {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * @param mixed[] $defaults
     * @return self
     */

    public function setDefaults(array $defaults)
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

    public function setDefault($key, $value)
    {
        $this->defaults[$key] = $value;
        return $this;
    }

    /**
     * @param mixed[] $metadata
     * @return self
     */

    public function setMetadataArray(array $metadata)
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */

    public function setMetadata($key, $value)
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * @param null|string|StrategyInterface $strategy
     * @return self
     */

    public function setStrategy($strategy)
    {
        $this->strategy = $strategy;
        return $this;
    }

    /**
     * @param Matcher $matcher
     * @return self
     */

    public function setMatcher(Matcher $matcher)
    {
        $this->matcher = $matcher;
        return $this;
    }

    public function setName($name)
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

    public function setConstraint($token, $regex)
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

    public function hasParam($key)
    {
        return isset($this->params[$key]);
    }

    /**
     * @param string $key
     * @return bool
     */

    public function hasDefault($key)
    {
        return isset($this->defaults[$key]);
    }

    /**
     * @param string $key
     * @return bool
     */

    public function hasMetadata($key)
    {
        return isset($this->metadata[$key]);
    }

}
