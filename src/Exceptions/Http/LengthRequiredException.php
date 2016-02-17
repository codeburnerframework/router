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
 * LengthRequiredException
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class LengthRequiredException extends HttpExceptionAbstract
{

    protected $code = 411;
    protected $message = "Length Required";

}
