<?php

namespace TestNamespace {
    class TestController {
        public function test()
        {
            return true;
        }
    }
}

namespace {
    class DummyController
    {
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
    }

    class ControllerCollectorResource
    {
        public function getSomeTest()
        {
            return true;
        }

        public function getAnotherTest($id)
        {
            return true;
        }

        public function getAnAnotherTest($id, $name = '')
        {
            return true;
        }

        /**
         * @param integer $id
         * @param string $name
         */
        public function getLastTest($id, $name = '')
        {
            return true;
        }
    }

    class ResourceController
    {
        public function index() {
            return 'index';
        }

        public function make() {
            return 'make';
        }

        public function create() {
            return 'create';
        }

        public function show() {
            return 'show';
        }

        public function edit() {
            return 'edit';
        }

        public function update() {
            return 'update';
        }

        public function delete() {
            return 'delete';
        }
    }
}
