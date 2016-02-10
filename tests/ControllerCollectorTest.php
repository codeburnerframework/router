<?php

use Codeburner\Router\Collector;
use Codeburner\Router\Matcher;

class ControllerCollectorTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var \Codeburner\Router\Collector
     */

    public $collector;

    /**
     * @var \Codeburner\Router\Matcher
     */
    public $matcher;

    public function setUp()
    {
        $this->collector = new Collector;
        $this->matcher = new Matcher($this->collector);
    }

    public function test_Controller_PathCompose_OptionalVars()
    {
        $this->collector->controller('Foo\FstController');

        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/fst/foo'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/fst/bar/1'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/fst/foo/bar/1'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/fst/foo/bar/1/2'));
    }

    public function test_MultipleController()
    {
        $this->collector->controllers(['Foo\FstController', 'Foo\SndController']);

        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/fst/foo'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/fst/bar/1'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/fst/foo/bar/1'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/fst/foo/bar/1/2'));

        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/snd/foo'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/snd/bar/1'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/snd/foo/bar/1'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/snd/foo/bar/1/2'));
    }

    public function test_ControllerWithoutPrefix()
    {
        $this->collector->controllerWithoutPrefix('Foo\FstController');

        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/foo'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/bar/1'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/foo/bar/1'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/foo/bar/1/2'));
    }

    public function test_MultipleControllerWithoutPrefix()
    {
        $this->collector->controllersWithoutPrefix(['Foo\FstController', 'Foo\SndController']);

        // only the SndController methods will be collected.

        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/foo'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/bar/1'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/foo/bar/1'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/foo/bar/1/2'));
    }

    public function test_Alias()
    {
        $this->collector->controller('Foo\FstController', 'trd');

        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/trd/foo'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/trd/bar/1'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/trd/foo/bar/1'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/trd/foo/bar/1/2'));
    }

    public function test_ControllerActionJoin()
    {
        $this->collector->setControllerActionJoin("-");
        $this->collector->controller('Foo\FstController');

        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/fst/foo-bar/1'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/fst/foo-bar/1/2'));
    }

    public function test_Annotations()
    {
        $this->collector->controller('Foo\TrdController');
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/annotated/foo/1'));
        
        $this->setExpectedException('Codeburner\Router\Exceptions\NotFoundException');
        $this->matcher->match('get', '/annotated/foo/a');
    }

}
