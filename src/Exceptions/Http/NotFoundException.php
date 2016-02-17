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
 * NotFoundException
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class NotFoundException extends HttpExceptionAbstract
{

    protected $code = 404;
    protected $message = "Not Found";

}
