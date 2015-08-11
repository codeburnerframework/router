<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router\Strategies\Collector;

/**
 * Codeburner Router Component.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @see https://github.com/codeburnerframework/router
 */
class ConcreteResourceStrategy
{

    /**
     * The route master collector.
     *
     * @var \Codeburner\Router\Collector
     */
    protected $collector;
    
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
        'delete' => ['delete', '/:name/{id}']
    ];

    /**
     * Construct the route dispatcher.
     *
     * @param \Codeburner\Router\Collector $collector The collector to save routes.
     */
    public function __construct($collector)
    {
        $this->collector = $collector;
    }

    /**
     * Resource routing allows you to quickly declare all of the common routes for a given resourceful controller. 
     * Instead of declaring separate routes for your index, show, new, edit, create, update and destroy actions, 
     * a resourceful route declares them in a single line of code
     *
     * @param string|object $controller The controller name or representation.
     * @param array         $options Some options like, 'as' to name the route pattern, 'only' to
     *                               explicty say that only this routes will be registered, and 
     *                               except that register all the routes except the indicates.
     */
    public function resource($controller, array $options = array())
    {
        $name = $this->getName($controller, $options);
        $actions = $this->getActions($options);

        foreach ($actions as $action => $map) {
            $this->collector->match($map[0], str_replace(':name', $name, $map[1]), 
                is_string($controller) ? "$controller#$action" : [$controller, $action]);
        }
    }

    /**
     * Get the name of controller or an defined name, that will be used to make the URis.
     *
     * @return string
     */
    protected function getName($controller, array $options)
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
     * Parse the options to find out what actions will be registered.
     *
     * @return string
     */
    protected function getActions($options)
    {
        $actions = $this->map;

        if (isset($options['only'])) {
            $actions = $this->getFilteredActions($options['only'], true);
        }

        if (isset($options['except'])) {
            $actions = $this->getFilteredActions($options['except'], false);
        }

        return $actions;
    }

    protected function getFilteredActions($methods, $exists)
    {
        $actions = $this->map;
        $methods = array_change_key_case(array_flip($methods), CASE_LOWER);

        foreach ($actions as $action => $map) {
            if ((isset($methods[$map[0]]) && !$exists)
                    || (!isset($methods[$map[0]]) && $exists)) {
                unset($actions[$action]);
            }   
        }

        return $actions;
    }

}
