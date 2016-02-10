<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2016 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router\Exceptions;

/**
 * NotFoundException
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class NotFoundException extends \Exception
{

    /**
     * The HTTP method from request.
     *
     * @var string
     */

    public $requestedMethod;

    /**
     * The requested Path.
     *
     * @var string
     */

    public $requestedPath;
    
    /**
     * Exception constructor.
     *
     * @param string  $requestedMethod  The request HTTP method.
     * @param string  $requestedPath    The request Path.
     * @param string  $message          The exception error message.
     * @param integer $code             The exception error code.
     */

    public function __construct($requestedMethod, $requestedPath, $message = null, $code = 405)
    {
        $this->requestedMethod = $requestedMethod;
        $this->requestedPath = $requestedPath;
        parent::__construct($message, $code);
    }
	
}
