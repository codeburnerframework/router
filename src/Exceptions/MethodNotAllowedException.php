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
 * Exception thrown when none route is matched, but a similar
 * route is found in another http method.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class MethodNotAllowedException extends BadRouteException
{

    /**
     * The HTTP method from request.
     *
     * @var string
     */

    public $requestedMethod;

    /**
     * The requested URi.
     *
     * @var string
     */

    public $requestedUri;

    /**
     * All the allowed HTTP methods and routes for the request.
     *
     * @var array
     */

    public $allowedMethods;
	
    /**
     * Exception constructor.
     *
     * @param string  $requestedMethod The request HTTP method.
     * @param string  $requestedUri    The request URi.
     * @param array   $allowedMethods  All the allowed HTTP methods and routes for the request.
     * @param string  $message         The exception error message.
     * @param integer $code            The exception error code.
     */

    public function __construct($requestedMethod, $requestedUri, array $allowedMethods, $message = null, $code = 405)
    {
        $this->requestedMethod = $requestedMethod;
        $this->requestedUri    = $requestedUri;
        $this->allowedMethods  = $allowedMethods;
        parent::__construct($message, $code);
    }

    /**
     * Verify if the given HTTP method is allowed for the request.
     *
     * @param string $method An HTTP method
     * @return bool
     */

    public function can($method)
    {
        return in_array(strtolower($method), $this->allowedMethods);
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
        return implode(', ', $this->allowedMethods);
    }

}
