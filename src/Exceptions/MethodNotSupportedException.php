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
 * Exception thrown when none route is matched, but a similar route is found in another HTTP method.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class MethodNotSupportedException extends \Exception
{

    public function __construct($method) {
        $this->message = "The HTTP method '$method' is not supported by the route collector.";
    }

}
