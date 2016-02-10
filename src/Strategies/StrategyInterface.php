<?php 

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2016 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router\Strategies;

/**
 * An interface that homogenizes all the dispatch strategies.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

interface StrategyInterface
{

    /**
     * Dispatch the matched route action.
     *
     * @param \Codeburner\Router\Route $route
     * @return mixed The response of request.
     */

    public function call(\Codeburner\Router\Route $route);

}
