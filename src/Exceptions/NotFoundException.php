<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2015 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router\Exceptions;

/**
 * Codeburner Router Component.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @see https://github.com/codeburnerframework/router
 */

class NotFoundException extends \Exception
{

    /**
     * The HTTP method from request.
     *
     * @var string
     */
    public $requested_method;

    /**
     * The requested URi.
     *
     * @var string
     */
    public $requested_uri;
    
    /**
     * Exception constructor.
     *
     * @param string  $requested_method The request HTTP method.
     * @param string  $requested_uri    The request URi.
     * @param string  $message          The exception error message.
     * @param integer $code             The exception error code.
     */
    public function __construct($requested_method, $requested_uri, $message = null, $code = 405)
    {
        $this->requested_method = $requested_method;
        $this->requested_uri    = $requested_uri;

        parent::__construct($message, $code);
    }
	
}
