<?php

/**
 * Codeburner Framework.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 * @copyright 2016 Alex Rohleder
 * @license http://opensource.org/licenses/MIT
 */

namespace Codeburner\Router;

use Codeburner\Router\Exceptions\BadRouteException;

/**
 * All the parsing route paths logic are maintained by this class.
 *
 * @author Alex Rohleder <contato@alexrohleder.com.br>
 */

class Parser
{

    /**
     * These regex define the structure of a dynamic segment in a pattern.
     *
     * @var string
     */

    const DYNAMIC_REGEX = "{\s*(\w*)\s*(?::\s*([^{}]*(?:{(?-1)}*)*))?\s*}";

    /**
     * Some regex wildcards for easily definition of dynamic routes. ps. all keys and values must start with :
     *
     * @var array
     */

    protected $wildcards = [
        ":uid"     => ":uid-[a-zA-Z0-9]",
        ":slug"    => ":[a-z0-9-]",
        ":string"  => ":\w",
        ":int"     => ":\d",
        ":integer" => ":\d",
        ":float"   => ":[-+]?\d*?[.]?\d",
        ":double"  => ":[-+]?\d*?[.]?\d",
        ":hex"     => ":0[xX][0-9a-fA-F]",
        ":octal"   => ":0[1-7][0-7]",
        ":bool"    => ":1|0|true|false|yes|no",
        ":boolean" => ":1|0|true|false|yes|no",
    ];

    /**
     * Separate routes pattern with optional parts into n new patterns.
     *
     * @param string $pattern
     *
     * @throws BadRouteException
     * @return array
     */

    public function parsePattern(string $pattern) : array
    {
        $withoutClosing = rtrim($pattern, "]");
        $closingNumber  = strlen($pattern) - strlen($withoutClosing);

        $segments = preg_split("~" . self::DYNAMIC_REGEX . "(*SKIP)(*F)|\[~x", $withoutClosing);
        $this->parseSegments($segments, $closingNumber, $withoutClosing);

        return $this->buildSegments($segments);
    }

    /**
     * Parse all the possible patterns seeking for an incorrect or incompatible pattern.
     *
     * @param string[] $segments       Segments are all the possible patterns made on top of a pattern with optional segments.
     * @param int      $closingNumber  The count of optional segments.
     * @param string   $withoutClosing The pattern without the closing token of an optional segment. aka: ]
     *
     * @throws BadRouteException
     */

    protected function parseSegments(array $segments, int $closingNumber, string $withoutClosing)
    {
        if ($closingNumber !== count($segments) - 1) {
            if (preg_match("~" . self::DYNAMIC_REGEX . "(*SKIP)(*F)|\]~x", $withoutClosing)) {
                   throw new BadRouteException(BadRouteException::OPTIONAL_SEGMENTS_ON_MIDDLE);
            } else throw new BadRouteException(BadRouteException::UNCLOSED_OPTIONAL_SEGMENTS);
        }
    }

    /**
     * @param string[] $segments
     *
     * @throws BadRouteException
     * @return array
     */

    protected function buildSegments(array $segments) : array
    {
        $pattern  = "";
        $patterns = [];
        $wildcardTokens = array_keys($this->wildcards);
        $wildcardRegex  = $this->wildcards;

        foreach ($segments as $n => $segment) {
            if ($segment === "" && $n !== 0) {
                throw new BadRouteException(BadRouteException::EMPTY_OPTIONAL_PARTS);
            }

            $patterns[] = $pattern .= str_replace($wildcardTokens, $wildcardRegex, $segment);
        }

        return $patterns;
    }

    /**
     * @return string[]
     */

    public function getWildcards() : array
    {
        $wildcards = [];
        foreach ($this->wildcards as $token => $regex)
            $wildcards[substr($token, 1)] = substr($regex, 1);
        return $wildcards;
    }

    /**
     * @return string[]
     */

    public function getWildcardTokens() : array
    {
        return $this->wildcards;
    }

    /**
     * @param string $wildcard
     * @return string|null
     */

    public function getWildcard(string $wildcard)
    {
        return isset($this->wildcards[":$wildcard"]) ? substr($this->wildcards[":$wildcard"], 1) : null;
    }

    /**
     * @param string $wildcard
     * @param string $pattern
     *
     * @return self
     */

    public function setWildcard(string $wildcard, string $pattern) : self
    {
        $this->wildcards[":$wildcard"] = ":$pattern";
        return $this;
    }

}
