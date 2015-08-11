<?php

class RouterrTest extends PHPUnit_Framework_TestCase
{

	public $collection;
	public $collector;
	public $dispatcher;
	public $methods = ['get', 'post', 'put', 'patch', 'delete'];

	public function setUp()
	{
		$this->collection = new Codeburner\Router\Collection;
		$this->collector  = new Codeburner\Router\Collector($this->collection);
		$this->dispatcher = new Codeburner\Router\Dispatcher('', $this->collection);

		parent::setUp();
	}

	public function testActionTypeClassMethod()
	{
		$this->collector->get('/test', [new DummyController, 'staticRouteAction']);

		$this->assertTrue( $this->dispatcher->dispatch('GET', '/test') );
	}

	public function testActionTypeClassMethodString()
	{
		$this->collector->get('/test', 'DummyController#staticRouteAction');

		$this->assertTrue( $this->dispatcher->dispatch('GET', '/test') );
	}

	public function testActionTypeClosure()
	{
		$this->collector->get('/test', function () {
			return true;
		});

		$this->assertTrue( $this->dispatcher->dispatch('GET', '/test') );
	}

	public function testStaticRoutes()
	{
		foreach ($this->methods as $method)
		{
			$this->collector->match($method, '/test', [new DummyController, 'staticRouteAction']);

			$this->assertTrue( $this->dispatcher->dispatch($method, '/test') );
		}
	}

	public function testDinamicRoutes()
	{
		foreach ($this->methods as $method)
		{
			$this->collector->$method('/{test}', [new DummyController, 'dinamicRouteAction']);

			$this->assertTrue( $this->dispatcher->dispatch($method, '/somedata') );
		}
	}

	public function testDinamicRouteAction()
	{
		$this->collector->get('/{test}', 'TestNamespace\{test}Controller#test');

		$this->assertTrue( $this->dispatcher->dispatch('get', '/test') );
	}

	public function testCollection()
	{
		$collection = new Codeburner\Router\Collection;

		$collection->set('get', '/test', 'DummyController#staticRouteAction');

		$dispatcher = new Codeburner\Router\Dispatcher('', $collection);

		$this->assertTrue( $dispatcher->dispatch('get', '/test') );
	}

	public function testStaticNotFoundRoutes()
	{
        $this->setExpectedException('Codeburner\Router\Exceptions\NotFoundException');

		$this->collector->get('/test', [new DummyController, 'staticRouteAction']);

		$this->dispatcher->dispatch('GET', '/test_e');
	}

	public function testDinamicNotFoundRoutes()
	{
        $this->setExpectedException('Codeburner\Router\Exceptions\NotFoundException');

		$this->collector->get('/some/{test}', [new DummyController, 'dinamicRouteAction']);

		$this->dispatcher->dispatch('get', '/test_e');
	}

	public function testStaticMethodNotAllowedRoutes()
	{
        $this->setExpectedException('Codeburner\Router\Exceptions\MethodNotAllowedException');

		$this->collector->post('/test', [new DummyController, 'staticRouteAction']);

		$this->dispatcher->dispatch('get', '/test');
	}

	public function testDinamicMethodNotAllowedRoutes()
	{
        $this->setExpectedException('Codeburner\Router\Exceptions\MethodNotAllowedException');

		$this->collector->post('/some/{test}', [new DummyController, 'dinamicRouteAction']);

		$this->dispatcher->dispatch('get', '/some/test');
	}

	public function testAnyHttpMethodMethod()
	{
		$this->collector->any('/test', [new DummyController, 'staticRouteAction']);

		foreach ($this->methods as $method) {
			$this->assertTrue($this->dispatcher->dispatch($method, '/test'));
		}
	}

	public function testMatchMethod()
	{
		$methods = ['get', 'post'];

		$this->collector->match($methods, '/test', [new DummyController, 'staticRouteAction']);

		foreach ($methods as $method) {
			$this->assertTrue($this->dispatcher->dispatch($method, '/test'));
		}
	}

	public function testExceptMethod()
	{
		$methods = ['put', 'patch'];

		$this->collector->except($methods, '/test', [new DummyController, 'staticRouteAction']);

		foreach (array_diff($this->methods, $methods) as $method) {
			$this->assertTrue($this->dispatcher->dispatch($method, '/test'));
		}
	}

	public function testDinamicRoutePattern()
	{
		$this->collector->get('/{test:[0-9]+}', [new DummyController, 'dinamicRouteAction']);

		$this->assertFalse( $this->dispatcher->dispatch('GET', '/someStringData', true) );
		$this->assertTrue( $this->dispatcher->dispatch('GET', '/123') );
	}

}
