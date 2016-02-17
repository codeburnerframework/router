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
 * UnauthorizedException
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class UnauthorizedException extends HttpExceptionAbstract
{

    protected $code = 401;
    protected $message = "Unauthorized Exception";

}
