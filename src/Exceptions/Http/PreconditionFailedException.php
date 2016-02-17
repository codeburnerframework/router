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
 * PreconditionFailedException
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class PreconditionFailedException extends HttpExceptionAbstract
{

    protected $code = 412;
    protected $message = "Precondition Failed";

}
