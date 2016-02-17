<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2016 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router\Exceptions\Http;

/**
 * ServiceUnavailableException
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class ServiceUnavailableException extends HttpExceptionAbstract
{

    protected $code = 503;
    protected $message = "Service Unavailable";

}
