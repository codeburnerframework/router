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

class MethodNotAllowedException extends \Exception
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
     * All the allowed HTTP methods and routes for the request.
     *
     * @var array
     */
    public $allowed_methods;
	
    /**
     * Exception constructor.
     *
     * @param string  $requested_method The request HTTP method.
     * @param string  $requested_uri    The request URi.
     * @param array   $allowed_methods  All the allowed HTTP methods and routes for the request.
     * @param string  $message          The exception error message.
     * @param integer $code             The exception error code.
     */
    public function __construct($requested_method, $requested_uri, array $allowed_methods, $message = null, $code = 405)
    {
        $this->requested_method = $requested_method;
        $this->requested_uri    = $requested_uri;
        $this->allowed_methods  = $allowed_methods;

        parent::__construct($message, $code);
    }

    /**
     * Verify if the given HTTP method is allowed for the request.
     *
     * @param string An HTTP method
     * @return bool
     */
    public function can($method)
    {
        return isset($this->allowed_methods[strtoupper($method)]);
    }

    /**
     * The HTTP specification requires that a 405 Method Not Allowed response include the 
     * Allow: header to detail available methods for the requested resource.
     *
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html section 14.7
     * @return string
     */
    public function allowed()
    {
        return implode(', ', array_keys($this->allowed_methods));
    }

}
