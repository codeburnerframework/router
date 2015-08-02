<?php

namespace namespacetest {
	class test {
		public function test()
		{
			return true;
		}
	}
}

namespace {
	class DispatcherTest extends PHPUnit_Framework_TestCase
	{

		public $methods = ['get', 'post', 'put', 'patch', 'delete'];

		public function setUp()
		{
			$this->dispatcher = new Codeburner\Router\Dispatcher;
			parent::setUp();
		}

		public function staticRouteAction()
		{
			return true;
		}

		public static function staticRouteActionStatic()
		{
			return true;
		}

		public function dinamicRouteAction($test)
		{
			return !is_null($test);
		}

		public function testActionTypeClassMethod()
		{
			$this->dispatcher->get('/test', [$this, 'staticRouteAction']);

			$this->assertTrue( $this->dispatcher->dispatch('GET', '/test') );
		}

		public function testActionTypeClassMethodString()
		{
			$this->dispatcher->get('/test', 'DispatcherTest#staticRouteAction');

			$this->assertTrue( $this->dispatcher->dispatch('GET', '/test') );
		}

		public function testActionTypeClosure()
		{
			$this->dispatcher->get('/test', function () {
				return true;
			});

			$this->assertTrue( $this->dispatcher->dispatch('GET', '/test') );
		}

		public function testStaticRoutes()
		{
			foreach ($this->methods as $method)
			{
				$this->dispatcher->match($method, '/test', [$this, 'staticRouteAction']);

				$this->assertTrue( $this->dispatcher->dispatch($method, '/test') );
			}
		}

		public function testDinamicRoutes()
		{
			foreach ($this->methods as $method)
			{
				$this->dispatcher->$method('/{test}', [$this, 'dinamicRouteAction']);

				$this->assertTrue( $this->dispatcher->dispatch($method, '/somedata') );
			}
		}

		public function testDinamicRouteAction()
		{
			$this->dispatcher->get('/{test}', 'namespacetest\{test}#test');

			$this->assertTrue( $this->dispatcher->dispatch('get', '/test') );
		}

		public function testStaticNotFoundRoutes()
		{
	        $this->setExpectedException('Codeburner\Router\Exceptions\NotFoundException');

			$this->dispatcher->get('/test', [$this, 'staticRouteAction']);

			$this->dispatcher->dispatch('GET', '/test_e');
		}

		public function testDinamicNotFoundRoutes()
		{
	        $this->setExpectedException('Codeburner\Router\Exceptions\NotFoundException');

			$this->dispatcher->get('/a/{test}', [$this, 'dinamicRouteAction']);

			$this->dispatcher->dispatch('get', '/test_e');
		}

		public function testStaticMethodNotAllowedRoutes()
		{
	        $this->setExpectedException('Codeburner\Router\Exceptions\MethodNotAllowedException');

			$this->dispatcher->post('/test', [$this, 'staticRouteAction']);

			$this->dispatcher->dispatch('get', '/test');
		}

		public function testDinamicMethodNotAllowedRoutes()
		{
	        $this->setExpectedException('Codeburner\Router\Exceptions\MethodNotAllowedException');

			$this->dispatcher->post('/a/{test}', [$this, 'dinamicRouteAction']);

			$this->dispatcher->dispatch('get', '/a/test');
		}

		public function testAnyHttpMethodMethod()
		{
			$this->dispatcher->any('/test', [$this, 'staticRouteAction']);

			foreach ($this->methods as $method) {
				$this->assertTrue($this->dispatcher->dispatch($method, '/test'));
			}
		}

		public function testMatchMethod()
		{
			$methods = ['get', 'post'];

			$this->dispatcher->match($methods, '/test', [$this, 'staticRouteAction']);

			foreach ($methods as $method) {
				$this->assertTrue($this->dispatcher->dispatch($method, '/test'));
			}
		}

		public function testDinamicRoutePattern()
		{
			$this->dispatcher->get('/{test:[0-9]+}', [$this, 'dinamicRouteAction']);

			$this->assertFalse( $this->dispatcher->dispatch('GET', '/someStringData', true) );
			$this->assertTrue( $this->dispatcher->dispatch('GET', '/123') );
		}

	}
}
