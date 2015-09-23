<?php

use Codeburner\Router\Mapper;
use Codeburner\Router\Dispatcher;

class ControllerMapperTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->mapper = new Mapper;
        $this->mapper->controller('ControllerCollectorResource', false);

        $this->dispatcher = new Dispatcher($this->mapper);

        parent::setUp();
    }

    public function testStaticRegister()
    {
        $this->assertTrue( $this->dispatcher->dispatch('get', '/some/test') );
    }

    public function testDinamicRegister()
    {
        $this->assertTrue( $this->dispatcher->dispatch('get', '/another/test/22') );
    }

    public function testDinamicOptionalRegister()
    {
        $this->assertTrue( $this->dispatcher->dispatch('get', '/an/another/test/22/hehe') );
    }

    public function testDinamicConstraintError()
    {
        $this->setExpectedException('Codeburner\Router\Exceptions\NotFoundException');

        $this->dispatcher->dispatch('get', '/last/test/hehe/22');
    }

    public function testDinamicConstraintSuccess()
    {
        $this->assertTrue( $this->dispatcher->dispatch('get', '/last/test/22/hehe') );
    }

}
