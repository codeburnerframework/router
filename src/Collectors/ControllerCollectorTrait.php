<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2016 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router\Collectors;

use Codeburner\Router\Collector;
use Codeburner\Router\Group;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Reflector;

/**
 * Methods for enable the collector to make routes from a controller.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

trait ControllerCollectorTrait
{

    abstract public function getWildcards();
    abstract public function set($method, $pattern, $action);

    /**
     * Define how controller actions names will be joined to form the route pattern.
     *
     * @var string
     */

    protected $controllerActionJoin = "/";

    /**
     * Maps all the controller methods that begins with a HTTP method, and maps the rest of
     * name as a path. The path will be the method name with slashes before every camelcased 
     * word and without the HTTP method prefix, and the controller name will be used to prefix
     * the route pattern. e.g. ArticlesController::getCreate will generate a route to: GET articles/create
     *
     * @param string $controller The controller name
     * @param string $prefix
     *
     * @throws \ReflectionException
     * @return Group
     */

    public function controller($controller, $prefix = null)
    {
        $controller = new ReflectionClass($controller);
        $prefix     = $prefix === null ? $this->getControllerPrefix($controller) : $prefix;
        $methods    = $controller->getMethods(ReflectionMethod::IS_PUBLIC);
        return $this->collectControllerRoutes($controller, $methods, "/$prefix/");
    }

    /**
     * Maps several controllers at same time.
     *
     * @param string[] $controllers Controllers name.
     * @throws \ReflectionException
     * @return Group
     */

    public function controllers(array $controllers)
    {
        $group = new Group;
        foreach ($controllers as $controller)
            $group->set($this->controller($controller));
        return $group;
    }

    /**
     * Alias for Collector::controller but maps a controller without using the controller name as prefix.
     *
     * @param string $controller The controller name
     * @throws \ReflectionException
     * @return Group
     */

    public function controllerWithoutPrefix($controller)
    {
        $controller = new ReflectionClass($controller);
        $methods = $controller->getMethods(ReflectionMethod::IS_PUBLIC);
        return $this->collectControllerRoutes($controller, $methods, "/");
    }

    /**
     * Alias for Collector::controllers but maps a controller without using the controller name as prefix.
     *
     * @param string[] $controllers
     * @throws \ReflectionException
     * @return Group
     */

    public function controllersWithoutPrefix(array $controllers)
    {
        $group = new Group;
        foreach ($controllers as $controller)
            $group->set($this->controllerWithoutPrefix($controller));
        return $group;
    }

    /**
     * @param ReflectionClass $controller
     * @param string[] $methods
     * @param string $prefix
     *
     * @return Group
     */

    protected function collectControllerRoutes(ReflectionClass $controller, array $methods, $prefix)
    {
        $group = new Group;
        $controllerDefaultStrategy = $this->getAnnotatedStrategy($controller);

        /** @var ReflectionMethod $method */
        foreach ($methods as $method) {
            $name = preg_split("~(?=[A-Z])~", $method->name);
            $http = $name[0];
            unset($name[0]);
 
            if (strpos(Collector::HTTP_METHODS, $http) !== false) {
                $action   = $prefix . strtolower(implode($this->controllerActionJoin, $name));
                $dynamic  = $this->getMethodConstraints($method);
                $strategy = $this->getAnnotatedStrategy($method);

                /** @var \Codeburner\Router\Route $route */
                $route = $this->set($http, "$action$dynamic", [$controller->name, $method->name]);

                if ($strategy !== null) {
                       $route->setStrategy($strategy);
                } else $route->setStrategy($controllerDefaultStrategy);

                $group->set($route);
            }
        }

        return $group;
    }

    /**
     * @param ReflectionClass $controller
     *
     * @return string
     */

    protected function getControllerPrefix(ReflectionClass $controller)
    {
        preg_match("~\@prefix\s([a-zA-Z\\\_]+)~i", (string) $controller->getDocComment(), $prefix);
        return isset($prefix[1]) ? $prefix[1] : str_replace("controller", "", strtolower($controller->getShortName()));
    }

    /**
     * @param \ReflectionMethod
     * @return string
     */

    protected function getMethodConstraints(ReflectionMethod $method)
    {
        $beginPath = "";
        $endPath = "";

        if ($parameters = $method->getParameters()) {
            $types = $this->getParamsConstraint($method);

            foreach ($parameters as $parameter) {
                if ($parameter->isOptional()) {
                    $beginPath .= "[";
                    $endPath .= "]";
                }

                $beginPath .= $this->getPathConstraint($parameter, $types);
            }
        }

        return $beginPath . $endPath;
    }

    /**
     * @param ReflectionParameter $parameter
     * @param string[] $types
     * @return string
     */

    protected function getPathConstraint(ReflectionParameter $parameter, $types)
    {
        $name = $parameter->name;
        $path = "/{" . $name;
        return isset($types[$name]) ? "$path:{$types[$name]}}" : "$path}";
    }

    /**
     * @param ReflectionMethod $method
     * @return string[]
     */

    protected function getParamsConstraint(ReflectionMethod $method)
    {
        $params = [];
        preg_match_all("~\@param\s(" . implode("|", array_keys($this->getWildcards())) . "|\(.+\))\s\\$([a-zA-Z0-1_]+)~i",
            $method->getDocComment(), $types, PREG_SET_ORDER);

        foreach ((array) $types as $type) {
            // if a pattern is defined on Match take it otherwise take the param type by PHPDoc.
            $params[$type[2]] = isset($type[4]) ? $type[4] : $type[1];
        }

        return $params;
    }

    /**
     * @param ReflectionClass|ReflectionMethod $reflector
     * @return string|null
     */

    protected function getAnnotatedStrategy($reflector)
    {
        preg_match("~\@strategy\s([a-zA-Z\\\_]+)~i", (string) $reflector->getDocComment(), $strategy);
        return isset($strategy[1]) ? $strategy[1] : null;
    }

    /**
     * Define how controller actions names will be joined to form the route pattern.
     * Defaults to "/" so actions like "getMyAction" will be "/my/action". If changed to
     * "-" the new pattern will be "/my-action".
     *
     * @param string $join
     */

    public function setControllerActionJoin($join)
    {
        $this->controllerActionJoin = $join;
    }

    /**
     * @return string
     */

    public function getControllerActionJoin()
    {
        return $this->controllerActionJoin;
    }

}
