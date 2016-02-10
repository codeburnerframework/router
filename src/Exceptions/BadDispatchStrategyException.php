<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2016 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router\Exceptions;

/**
 * Exception throwed when the route dispatch strategy
 * is not a valid one, strategies must implement the DispatchStrategyInterface.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class BadDispatchStrategyException extends BadRouteException
{

    public function __construct($strategy) {
        parent::__construct("`$strategy` is not a valid route dispatch strategy, it must implement the `Codeburner\Router\DispatchStrategies\DispatchStrategyInterface` interface.");
    }
    
}
