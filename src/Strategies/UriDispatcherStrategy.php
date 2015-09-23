<?php 

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router\Strategies;

/**
 * Execute the matched route action with the parameters as args.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @since 1.0.0
 */
class UriDispatcherStrategy implements DispatcherStrategyInterface
{

    /**
     * @inheritDoc
     */

    public function dispatch($action, array $params)
    {
        if (is_array($action)) {
            return call_user_func_array([new $action[0], $action[1]], $params);
        } else {
            return call_user_func_array($action, $params);
        }
    }

}
