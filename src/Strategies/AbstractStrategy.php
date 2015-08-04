<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router\Strategies;

/**
 * Codeburner Router Component.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @see https://github.com/codeburnerframework/router
 */
abstract class AbstractStrategy
{

    /**
     * Dispache the matched route action.
     *
     * @param  string|array|closure $action The matched route action.
     * @param  array                $params The route parameters.
     *
     * @return mixed The response of request.
     */
    public abstract function dispatch($action, array $params);

    /**
     * String that will divide controller from methods in the action string.
     *
     * @var string
     */
    protected $divider = '#';

    /**
     * Resolve the given acction into a valid callable.
     *
     * @return callable
     */
    public function resolve($action, $params)
    {
        if (is_string($action)) {
            if (strpos($action, '{') !== false) {
                $action = $this->resolveDinamicAction($action, $params);
            }

            $action = explode($this->divider, $action);
            $action[0] = new $action[0];
        }

        return $action;
    }

    /**
     * Set a new controller/method divider for action string.
     *
     * @param string $divider A divider of controller and method into action string.
     */
    public function setActionDivider($divider)
    {
        $this->divider = (string) $divider;
    }

    /**
     * Get the current action divider.
     *
     * @return string
     */
    public function getActionDivider()
    {
        return $this->divider;
    }

    /**
     * Resolve dinamic action, inserting route parameters at requested points.
     *
     * @return string
     */
    protected function resolveDinamicAction($action, $params)
    {
        return str_replace(['{', '}'], '', str_replace(array_keys($params), array_values($params), $action));
    }

}