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
 * An interface that homogenizes all the dispatch strategies.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @since 1.0.0
 */

interface DispatcherStrategyInterface
{

    /**
     * Dispatch the matched route action.
     *
     * @param string|array|closure $action The matched route action.
     * @param array                $params The route parameters.
     *
     * @return mixed The response of request.
     */

    public function dispatch($action, array $params);
}
