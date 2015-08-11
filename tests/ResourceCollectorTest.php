<?php

class ResourceCollectorTest extends PHPUnit_Framework_TestCase
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
        $this->collection = new Codeburner\Router\Collection;
        $this->collector  = new Codeburner\Router\Collector($this->collection);
        $this->dispatcher = new Codeburner\Router\Dispatcher('', $this->collection);

        parent::setUp();
    }

    public function testCollector()
    {
        $this->collector->resource('ResourceController');

        foreach ($this->actions as $name => $action) {
            $this->assertTrue(
                $this->dispatcher->dispatch(
                    $action[0], 
                    str_replace(':name', 'resource', $action[1])
                ) === $name
            );
        }
    }

    public function testNamedCollector()
    {
        $this->collector->resource('ResourceController', ['as' => 'test']);

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
        $only = ['get', 'post'];
        $this->collector->resource('ResourceController', ['as' => 'test', 'only' => $only]);

        foreach ($only as $httpmethod) {
            foreach ($this->actions as $name => $action) {
                if ($action[0] === $httpmethod) {
                    $this->assertTrue(
                        $this->dispatcher->dispatch(
                            $action[0], 
                            str_replace(':name', 'test', $action[1])
                        ) === $name
                    );
                }
            }
        }
    }

    public function testOnlyPropertyException()
    {
        $this->setExpectedException('Codeburner\Router\Exceptions\MethodNotAllowedException');
        
        $only = ['get', 'post'];
        $this->collector->resource('ResourceController', ['as' => 'test', 'only' => $only]);

        foreach ($this->actions as $name => $action) {
            if (!in_array($action[0], $only)) {
                $this->dispatcher->dispatch(
                    $action[0], 
                    str_replace(':name', 'test', $action[1])
                );
            }
        }
    }

    public function testExceptProperty()
    {
        $except = ['get', 'post'];
        $this->collector->resource('ResourceController', ['as' => 'test', 'except' => $except]);

        foreach ($this->actions as $name => $action) {
            if (!in_array($action[0], $except)) {
                $this->assertTrue(
                    $this->dispatcher->dispatch(
                        $action[0], 
                        str_replace(':name', 'test', $action[1])
                    ) === $name
                );
            }
        }
    }

    public function testExceptPropertyException()
    {
        $this->setExpectedException('Codeburner\Router\Exceptions\NotFoundException');
        
        $except = ['get', 'post'];
        $this->collector->resource('ResourceController', ['as' => 'test', 'except' => $except]);

        foreach ($except as $httpmethod) {
            foreach ($this->actions as $name => $action) {
                if ($action[0] === $httpmethod) {
                    $this->dispatcher->dispatch(
                        $action[0], 
                        str_replace(':name', 'test', $action[1])
                    );
                }
            }
        }
    }

}
