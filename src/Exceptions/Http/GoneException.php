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
 * GoneException
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class GoneException extends HttpExceptionAbstract
{

    protected $code = 410;
    protected $message = "Gone";

}
