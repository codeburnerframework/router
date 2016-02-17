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
use RuntimeException;

/**
 * The action will receive one Psr\Http\Message\RequestInterface object and one array with parameters
 * from pattern, and the return will create a json response, with right headers.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class RequestJsonStrategy implements StrategyInterface
{

    use RequestAwareTrait;

    /**
     * @inheritdoc
     * @throws RuntimeException
     * @return ResponseInterface
     */

    public function call(Route $route)
    {
        try {
            $response = call_user_func($route->getAction(), $this->request, $route->getMergedParams());

            if (is_array($response)) {
                $this->response->getBody()->write(json_encode($response));
                $response = $this->response;
            }

            if ($response instanceof ResponseInterface) {
                return $response->withAddedHeader("content-type", "application/json");
            }
        } catch (HttpExceptionAbstract $e) {
            return $e->getJsonResponse($this->response);
        }

        throw new RuntimeException("Unable to determine a json response from action returned value.");
    }

}
