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
	use Codeburner\Routing\Dispatcher;

	class DispatcherTest extends PHPUnit_Framework_TestCase
	{

		public $methods = ['get', 'post', 'delete', 'put', 'patch', 'head', 'options'];

		public function setUp()
		{
			$this->dispatcher = new Dispatcher;
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
			$this->dispatcher->get('/test', 'DispatcherTest@staticRouteAction');

			$this->assertTrue( $this->dispatcher->dispatch('GET', '/test') );
		}

		public function testActionTypeStaticClassMethodString()
		{
			$this->dispatcher->get('/test', 'DispatcherTest::staticRouteActionStatic');

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
			foreach (['GET', 'POST', 'DELETE', 'PUT', 'PATCH', 'HEAD', 'OPTIONS'] as $method)
			{
				$this->dispatcher->map($method, '/test', [$this, 'staticRouteAction']);

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
			$this->dispatcher->get('/{test}', 'namespacetest\{test}@test');

			$this->assertTrue( $this->dispatcher->dispatch('get', '/test') );
		}

		public function testStaticNotFoundRoutes()
		{
	        $this->setExpectedException('Codeburner\Routing\Exceptions\NotFoundException');

			$this->dispatcher->get('/test', [$this, 'staticRouteAction']);

			$this->dispatcher->dispatch('get', '/test_e');
		}

		public function testDinamicNotFoundRoutes()
		{
	        $this->setExpectedException('Codeburner\Routing\Exceptions\NotFoundException');

			$this->dispatcher->get('/a/{test}', [$this, 'dinamicRouteAction']);

			$this->dispatcher->dispatch('get', '/test_e');
		}

		public function testStaticMethodNotAllowedRoutes()
		{
	        $this->setExpectedException('Codeburner\Routing\Exceptions\MethodNotAllowedException');

			$this->dispatcher->post('/test', [$this, 'staticRouteAction']);

			$this->dispatcher->dispatch('get', '/test');
		}

		public function testDinamicMethodNotAllowedRoutes()
		{
	        $this->setExpectedException('Codeburner\Routing\Exceptions\MethodNotAllowedException');

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

		public function testMapMethod()
		{
			$methods = ['get', 'post'];

			$this->dispatcher->map($methods, '/test', [$this, 'staticRouteAction']);

			foreach ($methods as $method) {
				$this->assertTrue($this->dispatcher->dispatch($method, '/test'));
			}
		}

		public function testGroupedStaticRoutes()
		{
			$this->dispatcher->group('test', function ($dispatcher) {
				$dispatcher->get('/somepage', [$this, 'staticRouteAction'], [], '', true);
			});

			$this->assertTrue( $this->dispatcher->dispatch('GET', '/test/somepage') );
		}

		public function testGroupedDinamicRoutes()
		{
			$this->dispatcher->group('{someDinamicVariable}', function ($dispatcher) {
				$dispatcher->get('/somepage', [$this, 'dinamicRouteAction']);
			});

			$this->assertTrue( $this->dispatcher->dispatch('GET', '/asd/somepage') );
		}

		public function testGroupedNamespacedRoutes()
		{
			$this->dispatcher->group(['prefix' => 'test', 'namespace' => 'namespacetest'], function ($dispatcher) {
				$dispatcher->get('/somepage', 'test@test', [], '', true);
			});

			$this->assertTrue( $this->dispatcher->dispatch('GET', '/test/somepage') );
		}

		public function testDinamicRoutePattern()
		{
			$this->dispatcher->get('/{test:[0-9]+}', [$this, 'dinamicRouteAction']);

			$this->assertFalse( $this->dispatcher->dispatch('GET', '/someStringData', true) );
			$this->assertTrue( $this->dispatcher->dispatch('GET', '/123') );
		}

		public function testUriMethod()
		{
			$this->dispatcher->get('/test', [$this, 'staticRouteAction'], [], 'testRouteName');

			$this->assertEquals('/test', $this->dispatcher->uri('testRouteName'));

			$this->dispatcher->get('/{test}', [$this, 'dinamicRouteAction'], [], 'testRouteName');

			$this->assertEquals('/test', $this->dispatcher->uri('testRouteName', ['test']));
		}

		public function testAliasMethod()
		{
			$this->dispatcher->get('/test', [$this, 'staticRouteAction']);

			$this->dispatcher->alias('testRouteName', '/test');

			$this->assertEquals('/test', $this->dispatcher->uri('testRouteName'));
		}

		public function testNamedRoutes()
		{
			$this->dispatcher->get('/test', [$this, 'staticRouteAction'], [], 'testRouteName');

			$this->assertEquals('/test', $this->dispatcher->uri('testRouteName'));
		}

		public function testRouteFiltersPass()
		{
			$this->dispatcher->filter('testfilter', function () {
				return true;
			});

			$this->dispatcher->get('/test', [$this, 'staticRouteAction'], 'testfilter');

			$this->assertTrue($this->dispatcher->dispatch('get', '/test'));
		}

		public function testRouteFiltersNotPass()
		{
	        $this->setExpectedException('Codeburner\Routing\Exceptions\UnauthorizedException');

			$this->dispatcher->filter('testfilter', function () {
				return false;
			});

			$this->dispatcher->get('/test', [$this, 'staticRouteAction'], 'testfilter');

			$this->dispatcher->dispatch('get', '/test');
		}

	}
}
