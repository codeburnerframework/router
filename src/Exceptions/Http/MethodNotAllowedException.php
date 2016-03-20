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
 * MethodNotAllowed
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class MethodNotAllowedException extends HttpExceptionAbstract
{

    /**
     * @var string
     */

    protected $requestedMethod;

    /**
     * @var string
     */

    protected $requestedUri;

    /**
     * @var string[]
     */

    protected $allowedMethods;

    /**
     * MethodNotAllowedException constructor.
     *
     * @param string $requestedMethod
     * @param string $requestedUri
     * @param string[] $allowedMethods
     */

    public function __construct(string $requestedMethod, string $requestedUri, array $allowedMethods)
    {
        $this->requestedMethod = $requestedMethod;
        $this->requestedUri = $requestedUri;
        $this->allowedMethods = $allowedMethods;
        $this->code = 405;
        $this->message = "Method Not Allowed";
    }

    /**
     * Verify if the given HTTP method is allowed for the request.
     *
     * @param string $method An HTTP method
     * @return bool
     */

    public function can(string $method) : bool
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

    public function allowed() : string
    {
        return implode(', ', $this->allowedMethods);
    }

}
