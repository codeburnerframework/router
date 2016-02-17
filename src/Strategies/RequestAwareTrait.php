<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2016 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router\Strategies;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Trait RequestAwareTrait
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

trait RequestAwareTrait
{

    /**
     * @var RequestInterface
     */

    protected $request;

    /**
     * @var ResponseInterface
     */

    protected $response;

    /**
     * RequestResponseStrategy constructor.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     */

    public function __construct(RequestInterface $request, ResponseInterface $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @return RequestInterface
     */

    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param RequestInterface $request
     * @return self
     */

    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return ResponseInterface
     */

    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param ResponseInterface $response
     * @return self
     */

    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
        return $this;
    }

}
