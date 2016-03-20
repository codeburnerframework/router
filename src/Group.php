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

    public function set($route) : self
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

    public function setRoute(Route $route) : self
    {
        $this->routes[] = $route;
        return $this;
    }

    /**
     * Return all grouped routes objects.
     *
     * @return Route[]
     */

    public function all() : array
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

    public function nth(int $number) : Route
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

    public function forget() : self
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

    public function setMethod(string $method)
    {
        foreach ($this->routes as $route)
            $route->setMethod($method);
        return $this;
    }

    /**
     * Set one action to all grouped routes.
     *
     * @param callable $action
     * @return self
     */

    public function setAction($action) : self
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

    public function setNamespace(string $namespace) : self
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

    public function setPrefix(string $prefix) : self
    {
        $prefix = "/" . ltrim($prefix, "/");
        $routes = [];
        foreach ($this->routes as $route)
            $routes[] = $route->setPattern(rtrim($prefix . $route->getPattern(), "/"));
        $this->routes = $routes;
        return $this;
    }

    /**
     * Set metadata to all grouped routes.
     *
     * @param string $key
     * @param string $value
     *
     * @return self
     */

    public function setMetadata(string $key, $value) : self
    {
        foreach ($this->routes as $route)
            $route->setMetadata($key, $value);
        return $this;
    }

    /**
     * Set a bunch of metadata to all grouped routes.
     *
     * @param mixed[] $metadata
     * @return self
     */

    public function setMetadataArray(array $metadata) : self
    {
        foreach ($this->routes as $route)
            $route->setMetadataArray($metadata);
        return $this;
    }

    /**
     * Set default parameters to all grouped routes.
     *
     * @param mixed[] $defaults
     * @return self
     */

    public function setDefaults(array $defaults) : self
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
     * @return self
     */

    public function setDefault(string $key, $value) : self
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

    public function setStrategy($strategy) : self
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

    public function setConstraint(string $name, string $regex) : self
    {
        foreach ($this->routes as $route)
            $route->setConstraint($name, $regex);
        return $this;
    }

    /**
     * Set a name to a Route.
     *
     * @param string $name
     * @return self
     */

    public function setName(string $name) : self
    {
        if (count($this->routes) > 1) {
            throw new \LogicException("You cannot set the same name to several routes.");
        }

        $this->routes[0]->setName($name);
        return $this;
    }

}
