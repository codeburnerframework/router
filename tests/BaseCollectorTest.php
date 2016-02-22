<?php

use Codeburner\Router\Collector;
use Codeburner\Router\Matcher;

class BaseCollectorTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var \Codeburner\Router\Collector
     */

    public $collector;

    /**
     * @var \Codeburner\Router\Matcher
     */
    public $matcher;

    public $methods = ['get', 'post', 'put', 'patch', 'delete'];

    public function setUp()
    {
        $this->collector = new Collector;
        $this->matcher = new Matcher($this->collector);
    }

    public function test_Matcher_StaticRoute_ClosureCallback()
    {
        $this->collector->set('get', '/', function () {
            return true;
        });

        $this->assertInstanceOf('Codeburner\Router\Route', $route = $this->matcher->match('get', '/'));

        if ($route) {
            $this->assertTrue($route->call());
        }
    }

    public function test_CollectWrongHTTPMethodException()
    {
        $this->setExpectedException('Codeburner\Router\Exceptions\MethodNotSupportedException');
        $this->collector->set('wrong', '/', function () {
            return true;
        });
    }

    public function test_OptionalSegments()
    {
        $this->collector->set('get', '/hello[/world]', "");
        $this->assertInstanceOf('Codeburner\Router\Route', $route = $this->matcher->match('get', '/hello'));
        $this->assertInstanceOf('Codeburner\Router\Route', $route = $this->matcher->match('get', '/hello/world'));
    }

    public function test_OptionalSegmentsOnMiddle()
    {
        $this->setExpectedException('Codeburner\Router\Exceptions\BadRouteException');
        $this->collector->set('get', '/hello[/world]/wrong', "");
    }

    public function test_UnclosedOptionalSegment()
    {
        $this->setExpectedException('Codeburner\Router\Exceptions\BadRouteException');
        $this->collector->set('get', '/hello[/world', "");
    }

    public function test_EmptyOptionalSegments()
    {
        $this->setExpectedException('Codeburner\Router\Exceptions\BadRouteException');
        $this->collector->set('get', '/hello[]', "");
    }

    public function test_Matcher_DynamicRoute_ActionParameters()
    {
        $this->collector->set('get', '/{id}', function ($id) {
            return $id == 1;
        });

        $this->assertInstanceOf('Codeburner\Router\Route', $route = $this->matcher->match('get', '/1'));

        if ($route) {
            $this->assertTrue($route->call());
        }
    }

    public function test_Matcher_DynamicRouteConstraints()
    {
        $this->collector->set('get', '/{id:\d}', function ($id) {
            return $id == 1;
        });

        $this->assertInstanceOf('Codeburner\Router\Route', $route = $this->matcher->match('get', '/1'));

        if ($route) {
            $this->assertTrue($route->call());
        }

        $this->setExpectedException('Codeburner\Router\Exceptions\Http\NotFoundException');
        $this->matcher->match('get', '/a');
    }

    public function test_ControllerAction()
    {
        $this->collector->set('get', '/', 'Foo\Bar::method');
        $this->assertTrue($this->matcher->match('get', '/')->call());
    }

    public function test_NotFoundException()
    {
        $this->setExpectedException('Codeburner\Router\Exceptions\Http\NotFoundException');
        $this->collector->set('get', '/', 'Foo\Bar::method');
        $this->matcher->match('get', '/foo');
    }

    public function test_MethodNotAllowedException()
    {
        $this->setExpectedException('Codeburner\Router\Exceptions\Http\MethodNotAllowedException');
        $this->collector->set('get', '/', 'Foo\Bar::method');
        $this->matcher->match('post', '/');
    }

    public function test_MethodNotAllowedExceptionMethods()
    {
        try {
            $this->collector->get("/", "");
            $this->matcher->match("post", "/");
        } catch (\Codeburner\Router\Exceptions\Http\MethodNotAllowedException $e) {
            $this->assertTrue($e->can("get"));
            $this->assertEquals("get", $e->allowed());
        }
    }

    public function test_AnyMethod()
    {
        $this->collector->any('/', 'Foo\Bar::method');

        foreach ($this->methods as $method) {
            $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match($method, '/'));
        }
    }

    public function test_ExceptMethod()
    {
        $excepts = ['get', 'post'];
        $this->setExpectedException('Codeburner\Router\Exceptions\Http\MethodNotAllowedException');
        $this->collector->except($excepts, '/', 'Foo\Bar::method');

        foreach ($excepts as $except) {
            $this->matcher->match($except, '/');
        }

        foreach (array_diff($this->methods, $excepts) as $method) {
            $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match($method, '/'));
        }
    }

    public function test_MatchMethod()
    {
        $methods = ['get', 'post'];
        $this->collector->match($methods, '/', 'Foo\Bar::method');

        foreach ($methods as $method) {
            $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match($method, '/'));
        }
    }

    public function test_HttpMethods()
    {
        foreach ($this->methods as $method) {
            $this->collector->$method('/', 'Foo\Bar::method');
            $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match($method, '/'));
        }
    }

    public function test_Group()
    {
        $group = $this->collector->group([
            $this->collector->get('/', 'Foo\Bar::method'),
            $this->collector->get('/foo', 'Foo\Bar::method')
        ]);

        $this->assertInstanceOf('Codeburner\Router\Group', $group);
        $this->assertTrue(count($group->all()) === 2);
    }

    public function test_GroupConstraint()
    {
        $group = $this->collector->group([
            $this->collector->get('/{id:int}', 'Foo\Bar::method')
        ]);

        $group->setConstraint('id', 'uid+');

        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/uid-1'));

        $this->setExpectedException('Codeburner\Router\Exceptions\Http\NotFoundException');
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/3'));
    }

    public function test_GroupMethod()
    {
        $group = $this->collector->group([
            $this->collector->get('/foo', 'Foo\Bar::method'),
            $this->collector->put('/bar', 'Foo\Bar::method')
        ]);

        $group->setMethod('post');

        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('post', '/foo'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('post', '/bar'));
    }

    public function test_GroupAction()
    {
        $group = $this->collector->group([
            $this->collector->get('/foo', 'Foo\Bar::method'),
            $this->collector->get('/bar', 'Foo\Bar::method')
        ]);

        $group->setAction(function () {
            return 2;
        });

        $this->assertEquals(2, $this->matcher->match('get', '/foo')->call());
        $this->assertEquals(2, $this->matcher->match('get', '/bar')->call());
    }

    public function test_GroupNamespace()
    {
        $group = $this->collector->group([
            $this->collector->get('/foo', 'Bar::method'),
            $this->collector->get('/bar', 'Bar::method')
        ]);

        $group->setNamespace('Foo\\');

        $this->assertTrue($this->matcher->match('get', '/foo')->call());
        $this->assertTrue($this->matcher->match('get', '/bar')->call());
    }

    public function test_GroupMetadata()
    {
        $group = $this->collector->group([
            $this->collector->get('/foo', 'Foo\Bar::method'),
            $this->collector->get('/bar', 'Foo\Bar::method')
        ]);

        $group->setMetadata('test', 23);
        $this->assertEquals(23, $this->matcher->match('get', '/foo')->getMetadata('test'));
    }

    public function test_GroupMetadataArray()
    {
        $group = $this->collector->group([
            $this->collector->get('/foo', 'Foo\Bar::method'),
            $this->collector->get('/bar', 'Foo\Bar::method')
        ]);

        $group->setMetadataArray(['test' => 23, 'test2' => 21]);
        $this->assertEquals(['test' => 23, 'test2' => 21], $this->matcher->match('get', '/foo')->getMetadataArray());
    }

    public function test_GroupDefault()
    {
        $group = $this->collector->group([
            $this->collector->get('/foo/{id}', 'Foo\Bar::method'),
            $this->collector->get('/bar/{id}', 'Foo\Bar::method')
        ]);

        $group->setAction(function ($test, $id) {
            return $id == 1 && $test == 23;
        });

        $group->setDefault('test', 23);
        $this->assertEquals(23, $this->matcher->match('get', '/foo/1')->getDefault('test'));
        $this->assertTrue($this->matcher->match('get', '/foo/1')->call());
    }

    public function test_GroupDefaults()
    {
        $group = $this->collector->group([
            $this->collector->get('/foo/{id}', 'Foo\Bar::method'),
            $this->collector->get('/bar/{id}', 'Foo\Bar::method')
        ]);

        $group->setAction(function ($test1, $test2, $id) {
            return $id == 1 && $test1 == 23 && $test2 == 21;
        });

        $group->setDefaults(['test1' => 23, 'test2' => 21]);
        $this->assertEquals(['test1' => 23, 'test2' => 21], $this->matcher->match('get', '/foo/1')->getDefaults());
        $this->assertTrue($this->matcher->match('get', '/foo/1')->call());
    }

    public function test_WildcardSupport()
    {
        $this->collector->get('/foo/{id:int+}', 'Foo\Bar::method');
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/foo/123'));
        $this->setExpectedException('Codeburner\Router\Exceptions\Http\NotFoundException');
        $this->matcher->match('get', '/foo/a');
    }

    public function test_CustomWildcardSupport()
    {
        $this->collector->getParser()->setWildcard('test', '\d');
        $this->collector->get('/foo/{id:test+}', 'Foo\Bar::method');
        $this->assertEquals("\d", $this->collector->getParser()->getWildcard('test'));
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/foo/123'));
        $this->setExpectedException('Codeburner\Router\Exceptions\Http\NotFoundException');
        $this->matcher->match('get', '/foo/a');
    }

    public function test_ControllerCreatingFunction()
    {
        $this->collector->get('/foo/{id:int+}', 'Foo\Bar::method');
        $this->assertInstanceOf('Codeburner\Router\Route', $route = $this->matcher->match('get', '/foo/123'));

        $this->assertTrue($route->call(function ($controller) {
            return new $controller;
        }));

        function creating($controller) {
            return new $controller;
        }

        $this->assertTrue($route->call("creating"));
        $this->assertTrue($route->call([$this, "controllerCreationFunction"]));
    }

    public function controllerCreationFunction($controller)
    {
        return new $controller;
    }

    public function test_MatcherBasepath()
    {
        $this->matcher->setBasePath('/foo');
        $this->assertEquals('/foo', $this->matcher->getBasePath());
        $this->collector->get('/bar', 'Foo\Bar::method');
        $this->assertInstanceOf('Codeburner\Router\Route', $this->matcher->match('get', '/foo/bar'));
    }

    public function test_DynamicRouteCallback()
    {
        $this->collector->get('/{name}/{method}', 'Foo\{name}::{method}');
        $this->assertTrue($this->matcher->match('get', '/bar/method')->call());
    }

    public function test_SetParserMethod()
    {
        $this->collector->setParser(new Foo\CustomParser);
        $this->assertInstanceOf('Foo\CustomParser', $this->collector->getParser());
        $this->setExpectedException('LogicException');
        $this->collector->get('/', 'Foo\Bar::method');
        $this->collector->setParser(new Foo\CustomParser);
    }

}
