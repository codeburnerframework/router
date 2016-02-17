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
 * ConflictException
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class ConflictException extends HttpExceptionAbstract
{

    protected $code = 409;
    protected $message = "Conflict";

}
