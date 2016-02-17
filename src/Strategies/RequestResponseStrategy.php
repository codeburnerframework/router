<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2016 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router\Strategies;

use Psr\Http\Message\ResponseInterface;
use Codeburner\Router\Exceptions\Http\HttpExceptionAbstract;
use Codeburner\Router\Route;

/**
 * The RequestResponseStrategy give an instance of Psr\Http\Message\RequestInterface,
 * Psr\Http\Message\Response interface and one array with parameters from pattern.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class RequestResponseStrategy implements StrategyInterface
{

    use RequestAwareTrait;

    /**
     * @inheritdoc
     * @return ResponseInterface
     */

    public function call(Route $route)
    {
        try {
            $response = call_user_func($route->getAction(), $this->request, $this->response, $route->getMergedParams());

            if ($response instanceof ResponseInterface) {
                return $response;
            }

            $this->response->getBody()->write((string) $response);
            return $this->response;
        } catch (HttpExceptionAbstract $e) {
            return $e->getResponse($this->response);
        }
    }

}
