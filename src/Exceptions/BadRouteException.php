<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router\Exceptions;

/**
 * Codeburner Router Component.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @see https://github.com/codeburnerframework/router
 */

class BadRouteException extends \Exception
{

    const UNSUPPORTED_HTTP_METHOD = "";
    const OPTIONAL_SEGMENTS_ON_MIDDLE = "Optional segments can only occur at the end of a route.";
    const UNCLOSED_OPTIONAL_SEGMENTS = "Number of opening [ and closing ] does not match.";
    const EMPTY_OPTIONAL_PARTS = "Empty optional part.";

}
