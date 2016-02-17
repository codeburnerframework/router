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
 * PaymentRequiredException
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class PaymentRequiredException extends HttpExceptionAbstract
{

    protected $code = 402;
    protected $message = "Payment Required";

}
