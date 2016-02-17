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
 * NotAcceptableException
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class NotAcceptableException extends HttpExceptionAbstract
{

    protected $code = 406;
    protected $message = "Not Acceptable";

}
