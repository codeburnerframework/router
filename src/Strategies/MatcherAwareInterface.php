<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2016 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router\Strategies;

/**
 * An interface that represent one object that needs to
 * know about the Matcher.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

interface MatcherAwareInterface
{

    /**
     * @param \Codeburner\Router\Matcher $matcher
     */

    public function setMatcher(\Codeburner\Router\Matcher $matcher);

    /**
     * @return \Codeburner\Router\Matcher
     */

    public function getMatcher();

}