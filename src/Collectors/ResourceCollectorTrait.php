<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2016 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router\Collectors;

/**
 * Just a fix to phpstorm parser, as it intends that Resource is
 * a datatype of php7 and not a class in router package.
 */

use Codeburner\Router\Resource as RouteResource;

/**
 * Methods for enable the collector to be resourceful and make
 * easily to build apis routes.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

trait ResourceCollectorTrait
{

    /**
     * @param string $method
     * @param string $pattern
     * @param string $action
     *
     * @return \Codeburner\Router\Group
     */

    abstract public function set($method, $pattern, $action);

    /**
     * A map of all routes of resources.
     *
     * @var array
     */

    protected $map = [
        "index"  => ["get",    "/{name}"],
        "make"   => ["get",    "/{name}/make"],
        "create" => ["post",   "/{name}"],
        "show"   => ["get",    "/{name}/{id:int+}"],
        "edit"   => ["get",    "/{name}/{id:int+}/edit"],
        "update" => ["put",    "/{name}/{id:int+}"],
        "delete" => ["delete", "/{name}/{id:int+}"],
    ];

    /**
     * Resource routing allows you to quickly declare all of the common routes for a given resourceful controller. 
     * Instead of declaring separate routes for your index, show, new, edit, create, update and destroy actions, 
     * a resourceful route declares them in a single line of code.
     *
     * @param string $resource The resource name.
     * @return RouteResource
     */

    public function resource(string ...$resources)
    {
        foreach ($resources as $resource) {
            $name = str_replace(["controller", "resource"], "", strtolower($resource));
            $resource = new RouteResource;

            foreach ($this->map as $action => $map) {
                $resource->set(
                    $this->set($map[0], str_replace("{name}", $map[1], $name) , [$resource, $action])
                         ->setName("$name.$action")
                );
            }
        }

        return $resource;
    }

    /**
     * Collect several resources at same time.
     *
     * @param array $controllers Several controller names as parameters or an array with all controller names.
     * @return RouteResource
     */

    public function resources(array $controllers)
    {
        $resource = new RouteResource;
        foreach ($controllers as $controller)
            $resource->set($this->resource($controller));
        return $resource;
    }

    /**
     * @param string $controller
     * @param array $options
     *
     * @return mixed
     */

    protected function getResourceName($controller, array $options)
    {
        return isset($options["as"]) ? $options["as"] : str_replace("controller", "", strtolower($controller));
    }

    /**
     * @param  array $options
     * @return array
     */

    protected function getResourceActions(array $options)
    {
        return isset($options["only"])   ? array_intersect_key($this->map, array_flip((array) $options["only"])) :
              (isset($options["except"]) ? array_diff_key($this->map, array_flip((array) $options["except"]))    : $this->map);
    }

    /**
     * @param string $action
     * @param string $path
     * @param string $name
     * @param string[] $options
     *
     * @return string
     */

    protected function getResourcePath($action, $path, $name, array $options)
    {
        return str_replace("{name}", $name,
            $action === "make" && isset($options["translate"]["make"]) ? str_replace("make", $options["translate"]["make"], $path) :
           ($action === "edit" && isset($options["translate"]["edit"]) ? str_replace("edit", $options["translate"]["edit"], $path) : $path));
    }

}
