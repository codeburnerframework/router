<?php

namespace Foo;
include __DIR__ . '/../vendor/autoload.php';

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

