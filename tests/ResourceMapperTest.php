<?php

use Codeburner\Router\Mapper;
use Codeburner\Router\Dispatcher;

class ResourceMapperTest extends PHPUnit_Framework_TestCase
{

    protected $actions = [
        'index' => ['get', '/:name'],
        'make' => ['get', '/:name/make'],
        'create' => ['post', '/:name'],
        'show' => ['get', '/:name/123'],
        'edit' => ['get', '/:name/123/edit'],
        'update' => ['put', '/:name/123'],
        'delete' => ['delete', '/:name/123']
    ];

    public function setUp()
    {
        $this->mapper = new Codeburner\Router\Mapper;
        $this->dispatcher = new Codeburner\Router\Dispatcher($this->mapper);

        parent::setUp();
    }

    public function testMapper()
    {
        $this->mapper->resource('ResourceController');

        foreach ($this->actions as $name => $action) {
            $this->assertTrue(
                $this->dispatcher->dispatch(
                    $action[0], 
                    str_replace(':name', 'resource', $action[1])
                ) === $name
            );
        }
    }

    public function testNamedMapper()
    {
        $this->mapper->resource('ResourceController', ['as' => 'test']);

        foreach ($this->actions as $name => $action) {
            $this->assertTrue(
                $this->dispatcher->dispatch(
                    $action[0], 
                    str_replace(':name', 'test', $action[1])
                ) === $name
            );
        }
    }

    public function testOnlyProperty()
    {
        $only = ['index', 'show'];
        $this->mapper->resource('ResourceController', ['as' => 'test', 'only' => $only]);

        foreach (array_intersect_key($this->actions, array_flip($only)) as $name => $action) {
            $this->assertTrue(
                $this->dispatcher->dispatch(
                    $action[0], 
                    str_replace(':name', 'test', $action[1])
                ) === $name
            );
        }
    }

    public function testOnlyPropertyException()
    {
        $this->setExpectedException('Codeburner\Router\Exceptions\MethodNotAllowedException');
        
        $only = ['index', 'show'];
        $this->mapper->resource('ResourceController', ['as' => 'test', 'only' => $only]);

        foreach (array_diff_key($this->actions, array_flip($only)) as $name => $action) {
            $this->assertFalse(
                $this->dispatcher->dispatch(
                    $action[0], 
                    str_replace(':name', 'test', $action[1])
                ) === $name
            );
        }
    }

    public function testExceptProperty()
    {
        $except = ['index', 'show'];
        $this->mapper->resource('ResourceController', ['as' => 'test', 'except' => $except]);

        foreach (array_diff_key($this->actions, array_flip($except)) as $name => $action) {
            $this->assertTrue(
                $this->dispatcher->dispatch(
                    $action[0], 
                    str_replace(':name', 'test', $action[1])
                ) === $name
            );
        }
    }

    public function testExceptPropertyException()
    {
        $this->setExpectedException('Codeburner\Router\Exceptions\MethodNotAllowedException');
        
        $except = ['index', 'show'];
        $this->mapper->resource('ResourceController', ['as' => 'test', 'except' => $except]);

        foreach (array_intersect_key($this->actions, array_flip($except)) as $name => $action) {
            $this->dispatcher->dispatch(
                $action[0], 
                str_replace(':name', 'test', $action[1])
            );
        }
    }

}
