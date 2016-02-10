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
 * Exception base, throwed when a given route pattern cannot
 * be parsed, or is wrong formatted.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class BadRouteException extends \Exception
{

    const OPTIONAL_SEGMENTS_ON_MIDDLE    = "Optional segments can only occur at the end of a route.";
    const UNCLOSED_OPTIONAL_SEGMENTS     = "Number of opening [ and closing ] does not match.";
    const EMPTY_OPTIONAL_PARTS           = "Empty optional part.";
    const WRONG_CONTROLLER_CREATION_FUNC = "The controller creation function passed is not callable.";

}
