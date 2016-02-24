<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2016 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router;

/**
 * Create paths based on registered routes.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class Path
{

    /**
     * @var Collector
     */

    protected $collector;

    /**
     * Link constructor.
     *
     * @param Collector $collector
     */

    public function __construct(Collector $collector)
    {
        $this->collector = $collector;
    }

    /**
     * Generate a path to a route named by $name.
     *
     * @param string $name
     * @param array $args
     *
     * @throws \BadMethodCallException
     * @return string
     */

    public function to($name, array $args = [])
    {
        $route = $this->collector->findNamedRoute($name);
        $parser = $this->collector->getParser();
        $pattern = $route->getPattern();

        preg_match_all("~" . $parser::DYNAMIC_REGEX . "~x", $pattern, $matches, PREG_SET_ORDER);

        foreach ((array) $matches as $key => $match) {
            if (!isset($args[$match[1]])) {
                throw new \BadMethodCallException("Missing argument '{$match[1]}' on creation of link for '{$name}' route.");
            }

            $pattern = str_replace($match[0], $args[$match[1]], $pattern);
        }

        return $pattern;
    }

}
