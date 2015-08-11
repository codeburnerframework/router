<?php

include '../container/src/container.php';
include '../container/src/containerAwareInterface.php';
include '../container/src/containerAwareTrait.php';
include 'src/Collection.php';
include 'src/Strategies/Collector/ConcreteControllerStrategy.php';
include 'src/Strategies/Collector/ConcreteResourceStrategy.php';
include 'src/Collector.php';
include 'src/Dispatcher.php';
include 'src/Strategies/Dispatcher/StrategyInterface.php';
include 'src/Strategies/Dispatcher/ConcreteUriStrategy.php';
include 'src/Strategies/Dispatcher/ConcreteInjectorStrategy.php';
include 'src/Exceptions/MethodNotAllowedException.php';
include 'src/Exceptions/NotFoundException.php';

include 'tests/Class.php';

class TestController {

    /**
     * blablabla
     *
     * @param integer $id Match (\d{5}) diosadjoiajodjaisjie
     */
    public function getSomeTest($id, $name = '')
    {
        echo "funfo $id $name";
    }
}

$c = new Codeburner\Router\Collector;

$c->get('/user/{id:\d+}[/{name}]', function ($id, $name = 'unknown') {
    echo 'hello ', $name, ' your id is ', $id;
});

$c->controller('TestController');

$c->get('/hehuhu', function (ResourceController $resourcecontroller, $id = 5456461) {
    echo $resourcecontroller->create(), $id;
});

try {
    $e = new Codeburner\Router\Strategies\Dispatcher\ConcreteInjectorStrategy;
    $e->setContainer(new Codeburner\Container\Container);
    $d = new Codeburner\Router\Dispatcher('', $c->getCollection(), $e);
    $d->dispatch('get', '/user/123/alex');
    //$d = new Codeburner\Router\Dispatcher('', $c->getCollection());
    //$d->dispatch('get', '/user/123/alex');
} catch (Codeburner\Router\Exceptions\NotFoundException $e) {
    die("request not found for {$e->requested_uri} on {$e->requested_method}");
} catch (Codeburner\Router\Exceptions\MethodNotAllowedException $e) {
    if ($e->can('get')) {
        $d->dispatch('get', $e->requested_uri.'/alex');
    }
}
