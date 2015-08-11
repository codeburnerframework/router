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
interface StrategyInterface
{
    

    /**
     * Register all the collector extension methods.
     *
     * @param \Codeburner\Router\Collector $collector
     *
     * @return mixed The response of request.
     */
    public function register(\Codeburner\Router\Collector $collector);
}
