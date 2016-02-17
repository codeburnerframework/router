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
 * ForbiddenException
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class ForbiddenException extends HttpExceptionAbstract
{

    protected $code = 403;
    protected $message = "Forbidden";

}
