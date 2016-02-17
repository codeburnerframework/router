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
 * BadRequestException
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class BadRequestException extends HttpExceptionAbstract
{

    protected $code = 400;
    protected $message = "Bad Request";

}
