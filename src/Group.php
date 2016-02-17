<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2016 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router;

/**
 * Group several routes and abstract operations applied to all.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class Group
{

    /**
     * All grouped route objects.
     *
     * @var Route[]
     */

    protected $routes;

    /**
     * Set a new Route or merge an existing group of routes.
     *
     * @param Group|Route $route
     * @return self
     */

    public function set($route)
    {
        if ($route instanceof Group) {
            foreach ($route->all() as $r)
                $this->routes[] = $r;
        } else  $this->routes[] = $route;
        return  $this;
    }

    /**
     * A fast way to register a route into the group
     *
     * @param Route $route
     * @return self
     */

    public function setRoute(Route $route)
    {
        $this->routes[] = $route;
        return $this;
    }

    /**
     * Return all grouped routes objects.
     *
     * @return Route[]
     */

    public function all()
    {
        return $this->routes;
    }

    /**
     * Get a specific route of the group, routes receive a key based on
     * the order they are added to the group.
     *
     * @param int $number
     * @return Route
     */

    public function nth($number)
    {
        return $this->routes[$number];
    }

    /**
     * Forget the registration of all grouped routes on to collector.
     * After the forget the route object will still exist but will not
     * count for the matcher.
     *
     * @return self
     */

    public function forget()
    {
        foreach ($this->routes as $route)
            $route->forget();
        return $this;
    }

    /**
     * Set one HTTP method to all grouped routes.
     *
     * @param string $method The HTTP Method
     * @return self
     */

    public function setMethod($method)
    {
        foreach ($this->routes as $route)
            $route->setMethod($method);
        return $this;
    }

    /**
     * Set one action to all grouped routes.
     *
     * @param string $action
     * @return self
     */

    public function setAction($action)
    {
        foreach ($this->routes as $route)
            $route->setAction($action);
        return $this;
    }

    /**
     * Set one namespace to all grouped routes.
     *
     * @param string $namespace
     * @return self
     */

    public function setNamespace($namespace)
    {
        foreach ($this->routes as $route)
            $route->setNamespace($namespace);
        return $this;
    }

    /**
     * Add a prefix to all grouped routes pattern.
     *
     * @param string $prefix
     * @return self
     */

    public function setPrefix($prefix)
    {
        $prefix = "/" . ltrim($prefix, "/");
        foreach ($this->routes as $route)
            $route->setPattern(rtrim($prefix . $route->getPattern(), "/"));
        return $this;
    }

    /**
     * Set metadata to all grouped routes.
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     */

    public function setMetadata($key, $value)
    {
        foreach ($this->routes as $route)
            $route->setMetadata($key, $value);
        return $this;
    }

    /**
     * Set a bunch of metadata to all grouped routes.
     *
     * @param mixed[] $metadata
     * @return $this
     */

    public function setMetadataArray(array $metadata)
    {
        foreach ($this->routes as $route)
            $route->setMetadataArray($metadata);
        return $this;
    }

    /**
     * Set default parameters to all grouped routes.
     *
     * @param mixed[] $defaults
     * @return $this
     */

    public function setDefaults(array $defaults)
    {
        foreach ($this->routes as $route)
            $route->setDefaults($defaults);
        return $this;
    }

    /**
     * Set a default parameter to all grouped routes.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */

    public function setDefault($key, $value)
    {
        foreach ($this->routes as $route)
            $route->setDefault($key, $value);
        return $this;
    }

    /**
     * Set one dispatch strategy to all grouped routes.
     *
     * @param string|Strategies\StrategyInterface $strategy
     * @return self
     */

    public function setStrategy($strategy)
    {
        foreach ($this->routes as $route)
            $route->setStrategy($strategy);
        return $this;
    }

    /**
     * Replace or define a constraint for all dynamic segments named by $name.
     *
     * @param string $name
     * @param string $regex
     *
     * @return self
     */

    public function setConstraint($name, $regex)
    {
        foreach ($this->routes as $route)
            $route->setConstraint($name, $regex);
        return $this;
    }

}
