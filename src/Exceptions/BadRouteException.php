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
 * Exception base, thrown when a route pattern cannot be parsed, or is wrong formatted.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class BadRouteException extends \Exception
{

    const OPTIONAL_SEGMENTS_ON_MIDDLE    = "Optional segments can only occur at the end of a route.";
    const UNCLOSED_OPTIONAL_SEGMENTS     = "Number of opening [ and closing ] does not match.";
    const EMPTY_OPTIONAL_PARTS           = "Empty optional part.";
    const WRONG_CONTAINER_WRAPPER_FUNC   = "The container wrapper function passed to `call` method in `Codeburner\\Router\\Route` is not callable.";
    const BAD_STRATEGY                   = "`%s` is not a valid route dispatch strategy, it must implement the `Codeburner\\Router\\Strategies\\StrategyInterface` interface.";

}
