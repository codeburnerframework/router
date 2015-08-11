<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router\Strategies\Dispatcher;

use Codeburner\Container\ContainerAwareInterface;
use Codeburner\Container\ContainerAwareTrait;

/**
 * Codeburner Router Component.
 *
 * @author Alex Rohleder <alexrohleder96@outlook.com>
 * @see https://github.com/codeburnerframework/router
 */
class ConcreteInjectorStrategy implements StrategyInterface, ContainerAwareInterface
{

    use ContainerAwareTrait;

    /**
     * Dispache the matched route action.
     *
     * @param  string|array|closure $action The matched route action.
     * @param  array                $params The route parameters.
     *
     * @return mixed The response of request.
     */
    public function dispatch($action, array $params)
    {
        if (is_array($action)) {
            return call_user_func_array([$this->container->make($action[0], $params, true), $action[1]], $params);
        } else {
            return $this->container->call($action, $params, true);
        }
    }

}
