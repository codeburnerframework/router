<?php

use Codeburner\Router\Mapper;
use Codeburner\Router\Dispatcher;

class RouterTest extends PHPUnit_Framework_TestCase
{

	public $mapper;
	public $dispatcher;
	public $methods = ['get', 'post', 'put', 'patch', 'delete'];

	public function setUp()
	{
		$this->mapper = new Codeburner\Router\Mapper;
		$this->dispatcher = new Codeburner\Router\Dispatcher($this->mapper);
		parent::setUp();
	}

	public function testActionTypeClassMethod()
	{
		$this->mapper->get('/test', 'DummyController#staticRouteAction');

		$this->assertTrue( $this->dispatcher->dispatch('get', '/test') );
	}

	public function testActionTypeClassMethodString()
	{
		$this->mapper->get('/test', 'DummyController#staticRouteAction');

		$this->assertTrue( $this->dispatcher->dispatch('get', '/test') );
	}

	public function testActionTypeClosure()
	{
		$this->mapper->get('/test', function () {
			return true;
		});

		$this->assertTrue( $this->dispatcher->dispatch('get', '/test') );
	}

	public function testStaticRoutes()
	{
		foreach ($this->methods as $method)
		{
			$this->mapper->$method('/test', 'DummyController#staticRouteAction');

			$this->assertTrue( $this->dispatcher->dispatch($method, '/test') );
		}
	}

	public function testDinamicRoutes()
	{
		foreach ($this->methods as $method)
		{
			$this->mapper->$method('/{test}', 'DummyController#dinamicRouteAction');

			$this->assertTrue( $this->dispatcher->dispatch($method, '/somedata') );
		}
	}

	public function testDinamicRouteAction()
	{
		$this->mapper->get('/{test}', 'TestNamespace\{test}Controller#test');

		$this->assertTrue( $this->dispatcher->dispatch('get', '/test') );
	}

	public function testStaticNotFoundRoutes()
	{
        $this->setExpectedException('Codeburner\Router\Exceptions\NotFoundException');

		$this->mapper->get('/test', 'DummyController#staticRouteAction');

		$this->dispatcher->dispatch('get', '/test_e');
	}

	public function testDinamicNotFoundRoutes()
	{
        $this->setExpectedException('Codeburner\Router\Exceptions\NotFoundException');

		$this->mapper->get('/some/{test}', 'DummyController#dinamicRouteAction');

		$this->dispatcher->dispatch('get', '/test_e');
	}

	public function testStaticMethodNotAllowedRoutes()
	{
        $this->setExpectedException('Codeburner\Router\Exceptions\MethodNotAllowedException');

		$this->mapper->post('/test', 'DummyController#staticRouteAction');

		$this->dispatcher->dispatch('get', '/test');
	}

	public function testDinamicMethodNotAllowedRoutes()
	{
        $this->setExpectedException('Codeburner\Router\Exceptions\MethodNotAllowedException');

		$this->mapper->post('/some/{test}', 'DummyController#dinamicRouteAction');

		$this->dispatcher->dispatch('get', '/some/test');
	}

	public function testAnyHttpMethodMethod()
	{
		$this->mapper->any('/test', 'DummyController#staticRouteAction');

		foreach ($this->methods as $method) {
			$this->assertTrue($this->dispatcher->dispatch($method, '/test'));
		}
	}

	public function testMatchMethod()
	{
		$methods = ['get', 'post'];

		$this->mapper->match($methods, '/test', 'DummyController#staticRouteAction');

		foreach ($methods as $method) {
			$this->assertTrue($this->dispatcher->dispatch($method, '/test'));
		}
	}

	public function testExceptMethod()
	{
		$methods = ['put', 'patch'];

		$this->mapper->except($methods, '/test', 'DummyController#staticRouteAction');

		foreach (array_diff($this->methods, $methods) as $method) {
			$this->assertTrue($this->dispatcher->dispatch($method, '/test'));
		}
	}

	public function testDinamicRoutePattern()
	{
        $this->setExpectedException('Codeburner\Router\Exceptions\NotFoundException');

		$this->mapper->get('/{test:[0-9]+}', 'DummyController#dinamicRouteAction');

		$this->dispatcher->dispatch('get', '/someStringData');
		$this->assertTrue( $this->dispatcher->dispatch('get', '/123') );
	}

}
