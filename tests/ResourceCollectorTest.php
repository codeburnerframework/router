<?php

use Codeburner\Router\Collector;
use Codeburner\Router\Matcher;

class ResourceCollectorTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var \Codeburner\Router\Collector
     */

    public $collector;

    /**
     * @var \Codeburner\Router\Matcher
     */
    public $matcher;

    public $actions = [
        'index'  => ['get'   , '/{name}'],
        'make'   => ['get'   , '/{name}/make'],
        'create' => ['post'  , '/{name}'],
        'show'   => ['get'   , '/{name}/123'],
        'edit'   => ['get'   , '/{name}/123/edit'],
        'update' => ['put'   , '/{name}/123'],
        'delete' => ['delete', '/{name}/123']
    ];

    public function setUp()
    {
        $this->collector = new Collector;
        $this->matcher = new Matcher($this->collector);
    }

    public function test_ResourceFulCollector_AsOption()
    {
        $this->collector->resource('Resource', ['as' => 'test']);

        foreach ($this->actions as $action) {
            $this->assertInstanceOf(
                'Codeburner\Router\Route',
                $this->matcher->match($action[0], str_replace('{name}', 'test', $action[1]))
            );
        }
    }

    public function test_MultipleResourceCollector()
    {
        $resources = ['Resource', 'AnotherResource'];
        $this->collector->resources($resources);

        foreach ($resources as $resource) {
            foreach ($this->actions as $action) {
                $this->assertInstanceOf(
                    'Codeburner\Router\Route',
                    $this->matcher->match($action[0], str_replace('{name}', strtolower($resource), $action[1]))
                );
            }
        }
    }

    public function test_OnlyOption()
    {
        $actions = ['index', 'show'];
        $this->collector->resource('Resource', ['as' => 'test', 'only' => $actions]);
        $this->setExpectedException('Codeburner\Router\Exceptions\Http\NotFoundException');
        $this->doTestActions($actions);
    }

    public function test_ExceptOption()
    {
        $actions = ['index', 'show'];
        $this->collector->resource('Resource', ['as' => 'test', 'except' => $actions]);
        $this->setExpectedException('Codeburner\Router\Exceptions\Http\MethodNotAllowedException');
        $this->doTestActions(array_diff(array_keys($this->actions), $actions));
    }

    public function test_OnlyMethod()
    {
        $actions = ['index', 'show'];
        $this->collector->resource('Resource', ['as' => 'test'])->only($actions);
        $this->setExpectedException('Codeburner\Router\Exceptions\Http\NotFoundException');
        $this->doTestActions($actions);
    }

    public function test_ExceptMethod()
    {
        $actions = ['index', 'show'];
        $this->collector->resource('Resource', ['as' => 'test'])->except($actions);
        $this->setExpectedException('Codeburner\Router\Exceptions\Http\MethodNotAllowedException');
        $this->doTestActions(array_diff(array_keys($this->actions), $actions));
    }

    public function test_Nesting()
    {
        $this->collector->resource('Resource', ['as' => 'fst'])->nest($this->collector->resource('Resource', ['as' => 'snd']));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/fst/1'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/fst/1/snd/2'));
    }

    public function test_MultipleNesting()
    {
        $this->collector->resource('Resource', ['as' => 'fst'])->nest(
            $this->collector->resource('Resource', ['as' => 'snd'])->nest(
                $this->collector->resource('Resource', ['as' => 'trd'])
            )
        );

        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/fst/1'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/fst/1/snd/2'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/fst/1/snd/2/trd/3'));
    }

    public function test_Shallow()
    {
        $this->collector->resource('Resource', ['as' => 'fst'])->shallow(
            $this->collector->resource('Resource', ['as' => 'snd'])
        );

        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/fst'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/fst/1'));
        
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/fst/1/snd'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('post', '/fst/1/snd'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/fst/1/snd/make'));

        $this->setExpectedException('Codeburner\Router\Exceptions\Http\NotFoundException');
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('post', '/fst/1/snd/2/edit'));
    }

    private function doTestActions($actions)
    {
        foreach ($this->actions as $action => $inf) {
            if (in_array($action, $actions)) {
                $this->assertInstanceOf(
                    'Codeburner\Router\Route',
                    $this->matcher->match($inf[0], str_replace('{name}', 'test', $inf[1]))
                );
            } else {
                $this->matcher->match($inf[0], str_replace('{name}', 'test', $inf[1]));
            }
        }
    }

}
