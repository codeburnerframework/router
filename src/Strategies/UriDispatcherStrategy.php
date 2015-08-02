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
class UriDispatcherStrategy extends DispatcherStrategyAbstract implements DispatcherStrategyInterface
{

    /**
     * Dispache the matched route action.
     *
     * @param  string|array|closure $action The matched route action.
     * @param  array                $param  The route parameters.
     *
     * @return mixed The response of request.
     */
    public function dispatch($action, array $params)
    {
        return call_user_func_array($this->resolve($action, $params), $params);
    }

}