<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2016 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router\Exceptions\Http;

use Exception;
use Psr\Http\Message\ResponseInterface;

/**
 * Class HttpExceptionAbstract
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

abstract class HttpExceptionAbstract extends Exception
{

    public function getResponse(ResponseInterface $response) : ResponseInterface
    {
        return $response->withStatus($this->code, $this->message);
    }

    public function getJsonResponse(ResponseInterface $response) : ResponseInterface
    {
        $response->withAddedHeader("content-type", "application/json");

        if ($response->getBody()->isWritable()) {
            $response->getBody()->write(json_encode(["status-code" => $this->code, "reason-phrase" => $this->message]));
        }

        return $this->getResponse($response);
    }

}