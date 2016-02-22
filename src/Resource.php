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
 * Representation of a group of several routes with same
 * controller and respecting the resourceful actions.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class Resource extends Group
{

    /**
     * @inheritdoc
     * @throws \BadMethodCallException
     */

    public function setMethod($method)
    {
        throw new \BadMethodCallException("Resources can't chance they http method.");
    }

    /**
     * Remove the routes without the passed methods.
     *
     * @param string|string[] $methods
     * @return self
     */

    public function only($methods)
    {
        $this->filterByMethod((array) $methods, false);
        return $this;
    }

    /**
     * Remove the routes with the passed methods.
     *
     * @param string|string[] $methods
     * @return self
     */

    public function except($methods)
    {
        $this->filterByMethod((array) $methods, true);
        return $this;
    }

    /**
     * Forget the grouped routes filtering by http methods.
     *
     * @param string[] $methods
     * @param bool $alt Should remove?
     */

    private function filterByMethod(array $methods, $alt)
    {
        $methods = array_flip(array_map('strtolower', $methods));

        foreach ($this->routes as $route) {
            if (isset($methods[$route->getAction()[1]]) === $alt) {
                $route->forget();
            }
        }
    }

    /**
     * Translate the "make" or "edit" from resources path.
     *
     * @param string[] $translations
     * @return self
     */

    public function translate(array $translations)
    {
        foreach ($this->routes as $route) {
            $action = $route->getAction()[1];

            if ($action === "make" && isset($translations["make"])) {
                $route->setPatternWithoutReset(str_replace("make", $translations["make"], $route->getPattern()));
            } elseif ($action === "edit" && isset($translations["edit"])) {
                $route->setPatternWithoutReset(str_replace("edit", $translations["edit"], $route->getPattern()));
            }
        }

        return $this;
    }

    /**
     * Add a route or a group of routes to the resource, it means that
     * every added route will now receive the parameters of the resource, like id.
     *
     * @param Route|Group $route
     * @return self
     */

    public function member($route)
    {
        $resource = new self;
        $resource->set($route);
        $this->nest($resource);
    }

    /**
     * Nested routes capture the relation between a resource and another resource.
     *
     * @param self $resource
     * @return self
     */

    public function nest(self $resource)
    {
        foreach ($this->routes as $route) {
            if ($route->getAction()[1] === "show") {
                $this->set($resource->forget()->setPrefix($this->getNestedPrefix($route->getPattern()))); break;
            }
        }

        return $this;
    }

    /**
     * Nest resources but with only build routes with the minimal amount of information
     * to uniquely identify the resource.
     *
     * @param self $resource
     * @return self
     */

    public function shallow(self $resource)
    {
        $newResource = new self;
        $resource->forget();
        $routes = $resource->all();

        foreach ($routes as $route) {
            if (strpos("index make create", $route->getAction()[1]) !== false) {
                $newResource->set($route);
            }
        }

        return $this->nest($newResource);
    }

    /**
     * Resolve the nesting pattern, setting the prefixes based on
     * parent resources patterns.
     *
     * @param string $pattern
     * @return string
     */

    protected function getNestedPrefix($pattern)
    {
        $segments = explode("/", $pattern);
        $pattern = "";

        foreach ($segments as $index => $segment) {
            if (strpos($segment, "{") === 0) {
                   $pattern .= "/{" . $segments[$index - 1] . "_" . ltrim($segment, "{");
            } else $pattern .= $segment;
        }

        return $pattern;
    }

}
