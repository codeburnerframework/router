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
 * RequestTimeOutException
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class RequestTimeOutException extends HttpExceptionAbstract
{

    protected $code = 408;
    protected $message = "Request Time Out";

}
