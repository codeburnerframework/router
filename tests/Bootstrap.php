<?php

namespace Foo;
include __DIR__ . '/../vendor/autoload.php';

class CustomParser extends \Codeburner\Router\Parser
{

}

class CustomStrategy implements
    \Codeburner\Router\Strategies\StrategyInterface,
    \Codeburner\Router\Strategies\MatcherAwareInterface
{
    protected $matcher;

    public function call(\Codeburner\Router\Route $route)
    {
        return (int) (call_user_func($route->getAction()) && $this->matcher !== null);
    }

    public function setMatcher(\Codeburner\Router\Matcher $matcher)
    {
        $this->matcher = $matcher;
    }

    public function getMatcher()
    {
        return $this->matcher;
    }
}

class CustomWrongStrategy
{

}

class Psr7
{

    public function RequestResponse(
        \Psr\Http\Message\RequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response,
        array $args
    ) {
        return $args["id"];
    }

    public function RequestResponseException(
        \Psr\Http\Message\RequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response,
        array $args
    ) {
        throw new \Codeburner\Router\Exceptions\Http\NotFoundException;
    }

    public function ReturnResponse(
        \Psr\Http\Message\RequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response,
        array $args
    ) {
        return $response->withAddedHeader('test', 'test');
    }

    public function RequestJson(
        \Psr\Http\Message\RequestInterface $request,
        array $args
    ) {
        return ["test" => 1];
    }

    public function RequestJsonException(
        \Psr\Http\Message\RequestInterface $request,
        array $args
    ) {
        throw new \Codeburner\Router\Exceptions\Http\NotFoundException;
    }

    public function RequestJsonError(
        \Psr\Http\Message\RequestInterface $request,
        array $args
    ) {
        return "string";
    }

}

class Bar {
    public function method() {
        return true;
    }
}

class FstController
{
    public function getFoo() {
        return true;
    }

    public function getBar($id) {
        return true;
    }

    public function getFooBar($id, $name = '') {
        return true;
    }
}

class SndController extends FstController
{

}

/**
 * @prefix annotated
 */
class TrdController
{
    /**
     * @param int $id
     */
    public function getFoo($id) {
        return true;
    } 
}

class Resource
{
    public function index() {
        return 'index';
    }

    public function make() {
        return 'make';
    }

    public function create() {
        return 'create';
    }

    public function show() {
        return 'show';
    }

    public function edit() {
        return 'edit';
    }

    public function update() {
        return 'update';
    }

    public function delete() {
        return 'delete';
    }
}

class AnotherResource extends Resource
{

}

