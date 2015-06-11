<?php

use Codeburner\Routing\Dispatcher;

class DispatcherTest extends PHPUnit_Framework_TestCase
{

	public function setUp()
	{
		$this->dispatcher = new Dispatcher;
		parent::setUp();
	}

	public function staticRouteAction()
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
		foreach (['GET', 'POST', 'DELETE', 'PUT', 'PATCH', 'HEAD', 'OPTIONS'] as $method)
		{
			$this->dispatcher->map($method, '/{test}', [$this, 'dinamicRouteAction']);

			$this->assertTrue( $this->dispatcher->dispatch($method, '/somedata') );
		}
	}

	public function testGroupedStaticRoutes()
	{
		$this->dispatcher->group('test', function ($dispatcher) {
			$dispatcher->get('/somepage', [$this, 'staticRouteAction']);
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

}
