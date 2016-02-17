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
 * UnsupportedMediaTypeException
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class UnsupportedMediaTypeException extends HttpExceptionAbstract
{

    protected $code = 415;
    protected $message = "Unsupported Media Type";

}
