<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2016 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router\Strategies;

use Codeburner\Router\Route;
use Codeburner\Router\Exceptions\BadRouteException;

/**
 * The route enhancer strategy act like a bridge between
 * one route and it dispatch strategy. In this "bridge" operations
 * are made manipulating the route object.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

abstract class EnhancerAbstractStrategy implements StrategyInterface
{

    /**
     * Key used to store the real route strategy on metadata.
     *
     * @var string
     */

    protected $metadataStrategyKey = "strategy";

    /**
     * @inheritdoc
     * @throws BadRouteException
     */

    public function call(Route $route)
    {
        if ($route->hasMetadata($this->metadataStrategyKey)) {
               $route->setStrategy($route->getMetadata($this->metadataStrategyKey));
        } else $route->setStrategy(null);

        $this->enhance($route);

        return $route->call();
    }

    /**
     * Manipulate route object before the dispatch.
     *
     * @param Route $route
     * @return mixed
     */

    abstract public function enhance(Route $route);

}
