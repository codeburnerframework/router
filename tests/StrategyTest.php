<?php

use Codeburner\Router\Collector;
use Codeburner\Router\Matcher;
use Codeburner\Router\Strategies\RequestResponseStrategy;
use Codeburner\Router\Strategies\RequestJsonStrategy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Zend\Diactoros\Request;
use Zend\Diactoros\Response;

class StrategyTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var Collector
     */

    public $collector;

    /**
     * @var Matcher
     */
    public $matcher;

    /**
     * @var RequestInterface
     */
    public $request;

    /**
     * @var ResponseInterface
     */
    public $response;

    public function setUp()
    {
        $this->collector = new Collector;
        $this->matcher = new Matcher($this->collector);
        $this->request = new Request;
        $this->response = new Response;
    }

    public function test_Strategy_MatcherAwareStrategy()
    {
        $this->collector->get('/foo/{id:int+}', 'Foo\Bar::method')->setStrategy('Foo\CustomStrategy');
        $this->assertInstanceOf('Codeburner\Router\Route', $route = $this->matcher->match('get', '/foo/123'));

        if ($route) {
            $this->assertEquals(1, $route->call());
        }
    }

    public function test_StrategyException()
    {
        $this->collector->get('/foo/{id:int+}', 'Foo\Bar::method')->setStrategy('Foo\CustomWrongStrategy');
        $this->assertInstanceOf('Codeburner\Router\Route', $route = $this->matcher->match('get', '/foo/123'));

        if ($route) {
            $this->setExpectedException('\Codeburner\Router\Exceptions\BadRouteException');
            $this->assertTrue($route->call());
        }
    }

    public function test_RequestResponseStrategy()
    {
        $this->collector->get('/foo/{id:int+}', 'Foo\Psr7::RequestResponse')
            ->setStrategy(new RequestResponseStrategy($this->request, $this->response));
        $this->assertInstanceOf('Codeburner\Router\Route', $route = $this->matcher->match('get', '/foo/123'));

        if ($route) {
            $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response = $route->call());
        }
    }

    public function test_RequestJsonStrategy()
    {
        $this->collector->get('/foo/{id:int+}', 'Foo\Psr7::RequestJson')
            ->setStrategy(new RequestJsonStrategy($this->request, $this->response));
        $this->assertInstanceOf('Codeburner\Router\Route', $route = $this->matcher->match('get', '/foo/123'));

        if ($route) {
            $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response = $route->call());
            $this->assertEquals('{"test":1}', (string) $response->getBody());
        }
    }

    public function test_RequestResponseStrategy_ReturnJson()
    {
        $this->collector->get('/foo/{id:int+}', 'Foo\Psr7::ReturnResponse')
            ->setStrategy(new RequestResponseStrategy($this->request, $this->response));
        $this->assertInstanceOf('Codeburner\Router\Route', $route = $this->matcher->match('get', '/foo/123'));

        if ($route) {
            $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response = $route->call());
            $this->assertEquals('test', $response->getHeaderLine('test'));
        }
    }

}
